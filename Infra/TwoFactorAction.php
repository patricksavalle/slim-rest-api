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
        private array $actions = [];
        private ?string $utoken = null;

        public function addAction(string $phpfile, callable $callable, array $arguments): TwoFactorAction
        {
            assert(file_exists($phpfile));
            assert(is_callable($callable));
            $this->actions[] = [$phpfile, $callable, $arguments];
            return $this;
        }

        public function createToken(int $ttl = 60 * 60 * 24): TwoFactorAction
        {
            assert(sizeof($this->actions) > 0);
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
            foreach (Locker::unstash($args->utoken) as [$phpfile, $callable, $arguments]) {
                require_once $phpfile;
                $result = call_user_func_array($callable, $arguments);
            }
            return isset($result) ? $response->withJson($result) : $response;
        }

        // --------------------------------------------------------------------------------
        // Send the two factor code by mail
        // --------------------------------------------------------------------------------

        protected abstract function sendMail(string $receiver, string $sender, string $sendername, string $subject, string $body);

        /**
         * Send the authorisation request to the user
         * @throws Exception
         */
        public function sendToken(string $email, string $subject, ?string $template, stdClass $args): TwoFactorAction
        {
            if (empty($template)) {
                // use our default
                $body = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "twofactoraction.html");
            } elseif (filter_var($template, FILTER_VALIDATE_URL) !== false) {
                // user supplied an URL, retrieve content
                $body = file_get_contents($template);
            } elseif (is_file($template)) {
                // user supplied a local file, retrieve content
                $body = file_get_contents($template);
            } else {
                // probably an inline template
                $body = $template;
            }

            // add the token to the template variables
            $args->logintoken = $this->utoken;

            // Very simple template rendering, just iterate all object members and replace {{name}} with value
            foreach ($args as $key => $value) {
                $body = str_replace("{{" . $key . "}}", filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS), $body);
            }

            $this->sendMail($email, Ini::get('email_sender'), Ini::get('email_sendername'), $subject, $body);

            return $this;
        }

    }
}