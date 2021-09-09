<?php /** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace SlimRestApi\Infra {

    use Exception;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use stdClass;

    /**
     * Generic mechanism for authorising actions through an email link.
     *
     * Send an authorisation token to user:
     *
     *  $params = BodyParameters::get();
     *  (new YourTwoFactorAction)
     *      ->addAction(['Class', 'method'], [$params])
     *      ->createToken()
     *      ->sendToken($receiver, $loginurl, $subject, $instruction, $action);
     *
     * Handle the confirmed token, SLIM route-handler
     *
     *  $SlimApp->get("/<url_segment>/{utoken:[[:alnum:]]{32}}", new YourTwoFactorAction);
     *
     * This will cause the actions to be run. The result of the last action will be returned.
     */

    abstract class TwoFactorAction extends stdClass
    {
        // MUST BE PUBLIC, otherwise it will not be enumerated into the Locker
        public $actions = [];
        public $utoken = null;

        public function addAction(string $phpfile, callable $callable, array $arguments): TwoFactorAction
        {
            $this->actions[] = [$phpfile, $callable, $arguments];
            return $this;
        }

        public function createToken(int $ttl = 60 * 60 * 24): TwoFactorAction
        {
            // Create a token for the action, to execute to token use the __invoke methode
            $this->utoken = Locker::stash($this->actions, $ttl);
            unset($this->actions);
            return $this;
        }

        // ---------------------------------------------------------------------------------
        // This is the 2 factor callback. It executes the actions associated with the token
        // SLIM format
        // ---------------------------------------------------------------------------------

        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, stdClass $args): ResponseInterface
        {
            // Execute all requested actions in order, remember last result
            $result = null;
            foreach (Locker::unstash($args->utoken) as list($phpfile, $callable, $arguments)) {
                require_once $phpfile;
                $result = call_user_func_array($callable, $arguments);
            }
            return isset($result) ? $response->withJson($result) : $response;
        }

        // --------------------------------------------------------------------------------
        // Send the two factor code by mail
        // --------------------------------------------------------------------------------

        /** @noinspection PhpUnusedParameterInspection */
        protected abstract function sendMail(string $receiver, string $sender, string $sendername, string $subject, string $body);

        /**
         * Send the authorisation request to the user
         * @throws Exception
         */
        public function sendToken(string $receiver, string $loginurl, string $subject, string $instruction, string $action): TwoFactorAction
        {
            // template of email to be sent
            $template_url = Ini::get('email_twofactor_template');

            // In $args are the template variables for the email template
            $args = new stdClass;
            $args->utoken = $this->utoken;
            $args->sender = Ini::get('email_sender');
            $args->sendername = Ini::get('email_sendername');
            $args->subject = $subject;
            $args->instructions = $instruction;
            $args->action = $action;
            $args->receiver = $receiver;
            $args->loginurl = $loginurl;

            // Get the email template from the client
            $body = file_get_contents($template_url);
            if ($body === false) {
                throw new Exception('Cannot open email template: ' . $args->template_url);
            }

            // Very simple template rendering, just iterate all object members and replace name with value
            foreach ($args as $member => $value) {
                $body = str_replace("{{" . $member . "}}", filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS), $body);
            }

            $this->sendMail(
                $args->receiver,
                $args->sender,
                $args->sendername,
                $args->subject,
                $body
            );

            return $this;
        }

    }
}