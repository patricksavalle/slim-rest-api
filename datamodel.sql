-- ----------------------------------------------------------------------------
-- ----------------------------------------------------------------------------
-- TABLES
-- ----------------------------------------------------------------------------
-- ----------------------------------------------------------------------------

-- -----------------------------------------------------
-- Authentication tokens
-- -----------------------------------------------------

CREATE TABLE sessiontoken
(
  token       CHAR(32)     NOT NULL,
  userid      INT UNSIGNED NOT NULL,
  lastupdate  TIMESTAMP                DEFAULT CURRENT_TIMESTAMP,
  fingerprint CHAR(32)     NULL        DEFAULT NULL,
  PRIMARY KEY (token),
  INDEX (lastupdate)
);

-- -----------------------------------------------------
-- Secret tokens
-- -----------------------------------------------------

CREATE TABLE tokenstore
(
  hash               CHAR(32)  NOT NULL,
  json               JSON      NOT NULL,
  expirationdatetime TIMESTAMP NOT NULL,
  PRIMARY KEY (hash),
  INDEX (expirationdatetime)
);

-- -----------------------------------------------------
-- Holds the membership data
-- -----------------------------------------------------

CREATE TABLE member
(
  id                    INT UNSIGNED AUTO_INCREMENT         NOT NULL,

  -- always same as the uaddress of the profile folder of this user (trigger!)
  uaddress              CHAR(32)                            NOT NULL,

  -- always same as the name of the profile folder of this user (trigger!)
  email                 VARCHAR(50)                         NOT NULL,
  -- see http://stackoverflow.com/questions/1297272/how-long-should-sql-email-fields-be

  phone                 VARCHAR(30)                         NULL     DEFAULT NULL,
  status                ENUM (
    'invited',
    'registered',
    'activated',
    'deactivated',
    'suspended',
    'admin')                                                NOT NULL DEFAULT 'registered',
  password_hash         CHAR(60)                            NULL     DEFAULT NULL,
  -- preregistered users and 2fa-only users have no password'
  registerdatetime      TIMESTAMP                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lastlogindatetime     TIMESTAMP                           NULL     DEFAULT NULL,
  resetmessagesdatetime TIMESTAMP                           NULL     DEFAULT NULL,
  -- see http://stackoverflow.com/questions/20958/list-of-standard-lengths-for-database-fields
  firstname             VARCHAR(100)                        NULL     DEFAULT NULL,
  lastname              VARCHAR(100)                        NULL     DEFAULT NULL,
  gender                ENUM ('ms', 'mrs', 'mr')            NULL     DEFAULT NULL,
  fullname              VARCHAR(200) GENERATED ALWAYS AS (CONCAT(gender, '. ', firstname, ' ', lastname)) VIRTUAL,
  birthday              DATETIME                            NULL     DEFAULT NULL,
  address               VARCHAR(200)                        NULL     DEFAULT NULL,
  country_of_birth      CHAR(2)                             NULL     DEFAULT NULL,
  country_of_residence  CHAR(2)                             NULL     DEFAULT NULL,
  occupation            VARCHAR(50)                         NULL     DEFAULT NULL,
  nationality           CHAR(2)                             NULL     DEFAULT NULL,
  language              CHAR(2)                             NOT NULL DEFAULT 'en',
  timezone              VARCHAR(40)                         NOT NULL DEFAULT 'europe/amsterdam',
  id1_name              VARCHAR(200)                        NULL     DEFAULT NULL,
  id1_number            VARCHAR(25)                         NULL     DEFAULT NULL,
  id1_expiration        DATETIME                            NULL     DEFAULT NULL,
  id1_type              ENUM ('passport', 'driverslicense') NULL     DEFAULT NULL,
  id1_nationality       CHAR(2)                             NULL     DEFAULT NULL,
  id2_name              VARCHAR(200)                        NULL     DEFAULT NULL,
  id2_number            VARCHAR(25)                         NULL     DEFAULT NULL,
  id2_expiration        DATETIME                            NULL     DEFAULT NULL,
  id2_type              ENUM ('passport', 'driverslicense') NULL     DEFAULT NULL,
  id2_nationality       CHAR(2)                             NULL     DEFAULT NULL,
  -- see http://stackoverflow.com/questions/3477347/php-tz-setting-length'
  currency              CHAR(3)                             NULL     DEFAULT 'eur',
  PRIMARY KEY (id),
  UNIQUE INDEX (uaddress),
  UNIQUE INDEX (email),
  UNIQUE INDEX (phone),
  INDEX (lastname)
);

-- ----------------------------------------------------------------------------
-- remember when a user was last logged in
-- ----------------------------------------------------------------------------

DELIMITER //

CREATE TRIGGER on_delete_token BEFORE DELETE ON sessiontoken FOR EACH ROW
  BEGIN
    UPDATE member
    SET lastlogindatetime = NOW()
    WHERE id = old.userid;
  END//

DELIMITER ;

-- ----------------------------------------------------------------------------
-- Delete sessions without activity for an hour
-- ----------------------------------------------------------------------------

CREATE EVENT expire_session
  ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM sessiontoken
  WHERE TIMESTAMPDIFF(MINUTE, lastupdate, NOW()) > 60;

-- ----------------------------------------------------------------------------
-- Delete tokens that are past the expirationdate
-- ----------------------------------------------------------------------------

CREATE EVENT expire_token
  ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM tokenstore
  WHERE expirationdatetime < NOW();

-- ----------------------------------------------------------------------------
-- ----------------------------------------------------------------------------
-- INITIALISATION
-- ----------------------------------------------------------------------------
-- ----------------------------------------------------------------------------

-- insert admins
INSERT INTO member (email, status, password_hash, uaddress)
VALUES
  ('patrick@patricksavalle.com', 'admin', '$2a$08$6qboYJwUg.bFezbbRF8G/uyHxGBmySTxx3E/IT/Ry/6QcTnCnVmyO', MD5(UUID())),
  ('info@handijk.nl', 'admin', '$2a$08$6qboYJwUg.bFezbbRF8G/uyHxGBmySTxx3E/IT/Ry/6QcTnCnVmyO', MD5(UUID()));

-- -----------------------------------------------------
-- ASSERT THE DATABASE IS CONFIGURED CORRECTLY
-- -----------------------------------------------------

DELIMITER //

CREATE PROCEDURE check_config()
  BEGIN
    DECLARE msg VARCHAR(255) DEFAULT '';
    IF @@global.event_scheduler <> 'ON'
    THEN
      SET msg = "Event scheduler not enabled";
      SIGNAL SQLSTATE '45001'
      SET MESSAGE_TEXT = msg;
    END IF;
    IF @@character_set_results <> 'utf8'
       OR @@character_set_client <> 'utf8'
       OR @@character_set_connection <> 'utf8'
       OR @@character_set_server <> 'utf8'
    THEN
      SET msg = "Not all character-set settings of server set to utf8 ";
      SIGNAL SQLSTATE '45001'
      SET MESSAGE_TEXT = msg;
    END IF;
  END//

DELIMITER ;

