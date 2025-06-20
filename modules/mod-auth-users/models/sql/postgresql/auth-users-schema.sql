
-- START :: PostgreSQL: Web/AuthUsers :: r.20250314 # smart.framework.v.8.7 ###

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;
SET default_tablespace = '';
SET default_with_oids = false;

-- Schema #

CREATE SCHEMA IF NOT EXISTS web;
COMMENT ON SCHEMA web IS 'Web Area';

SET search_path = web, pg_catalog;

BEGIN;

-- Table Structure: Web.AuthUsers # {{{SYNC-AUTH-USERS-DB-KEYS-MAX-LEN}}}

CREATE TABLE web.auth_users (
    id character varying(21) NOT NULL,
    registered timestamp without time zone DEFAULT (now())::timestamp(0) without time zone NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    cluster character varying(63) DEFAULT ''::character varying NOT NULL,
    email character varying(72) NOT NULL,
    password character varying(128) DEFAULT ''::character varying NOT NULL,
    passalgo smallint DEFAULT 0 NOT NULL,
    passresetotc character varying(128) DEFAULT ''::character varying NOT NULL,
    passresetcnt bigint DEFAULT 0 NOT NULL,
    passresetldt timestamp without time zone DEFAULT '1970-01-01 00:00:00'::timestamp(0) without time zone NOT NULL,
    fa2 text DEFAULT ''::text NOT NULL,
    allowfed character varying(255) DEFAULT ''::character varying NOT NULL,
    seckey text DEFAULT ''::text NOT NULL,
    signkeys text DEFAULT ''::text NOT NULL,
    priv character varying(255) DEFAULT ''::character varying NOT NULL,
    restr character varying(255) DEFAULT ''::character varying NOT NULL,
    iprestr character varying(255) DEFAULT ''::character varying NOT NULL,
    settings jsonb DEFAULT '{}'::jsonb NOT NULL,
    quota bigint DEFAULT 0 NOT NULL,
    name character varying(129) DEFAULT ''::character varying NOT NULL,
    data jsonb DEFAULT '{}'::jsonb NOT NULL,
    jwtserial character varying(21) DEFAULT ''::character varying NOT NULL,
    jwtsignature character varying(255) DEFAULT ''::character varying NOT NULL,
    lastseen timestamp without time zone DEFAULT '1970-01-01 00:00:00'::timestamp(0) without time zone NOT NULL,
    ipaddr cidr DEFAULT '0.0.0.0/32'::cidr NOT NULL,
    authlog text DEFAULT ''::text NOT NULL,
    CONSTRAINT web__auth_users__chk__id CHECK ((length((id)::text) = 21)),
    CONSTRAINT web__auth_users__chk__status CHECK (((status >= 0) AND (status <= 255))),
    CONSTRAINT web__auth_users__chk__email CHECK (((length((email)::text) >= 5) AND (length((email)::text) <= 72))),
    CONSTRAINT web__auth_users__chk__password CHECK (((length((password)::text) = 0) OR ((length((password)::text) >= 60) AND (length((password)::text) <= 128)))),
    CONSTRAINT web__auth_users__chk__passalgo CHECK (((passalgo >= 0) AND (passalgo <= 255))),
    CONSTRAINT web__auth_users__chk__passresetcnt CHECK ((passresetcnt >= 0)),
    CONSTRAINT web__auth_users__chk__quota CHECK ((quota >= '-1'::integer)),
    CONSTRAINT web__auth_users__chk__jwtserial CHECK (((length((jwtserial)::text) = 0) OR (length((jwtserial)::text) = 21))),
    CONSTRAINT web__auth_users__chk__jwtsignature CHECK (((length((jwtsignature)::text) = 0) OR (length((jwtsignature)::text) >= 22)))
);

COMMENT ON TABLE  web.auth_users              IS 'Web - Auth Users (Accounts) v.2025.03.14';
COMMENT ON COLUMN web.auth_users.id           IS 'Unique ID :: STRING(21) ; as UUID.seq-UUID.num ; ex: XYZABC028W-0243756788';
COMMENT ON COLUMN web.auth_users.registered   IS 'Account Registration Date and Time: YYYY-MM-DD HH:II:SS';
COMMENT ON COLUMN web.auth_users.status       IS 'Account Status :: SMALLINT(0..255) as UINT8 ; 0 = Not Verified ; 1 = Verified ; 2..255 custom ...';
COMMENT ON COLUMN web.auth_users.cluster      IS 'User assigned sub-domain cluster :: STRING(63) ; srv1.dom.ext';
COMMENT ON COLUMN web.auth_users.email        IS 'User Email, used as Auth UserName :: STRING(72) ; abc123@dom.ext';
COMMENT ON COLUMN web.auth_users.password     IS 'User Password Hash :: STRING(60..128) ; BCrypt or similar algo ...';
COMMENT ON COLUMN web.auth_users.passalgo     IS 'Pass Hash Algo :: SMALLINT(0..255) as UINT8 ; 0 = None (empty, federated identity) 1 = Plain (unused, unsecure) ; 2..255 others ; ex: 77 = SfPass ; 78 = SfAPass ; 123 = BCrypt';
COMMENT ON COLUMN web.auth_users.passresetotc IS 'Account Password Reset One Time Code';
COMMENT ON COLUMN web.auth_users.passresetcnt IS 'Account Password Reset Counter';
COMMENT ON COLUMN web.auth_users.passresetldt IS 'Account Password Reset Last Date and Time: YYYY-MM-DD HH:II:SS';
COMMENT ON COLUMN web.auth_users.fa2          IS '2FA Secret :: STRING(255) ; Base32 ; Encrypted';
COMMENT ON COLUMN web.auth_users.allowfed     IS 'Federated Login Allow :: STRING(255) ; `` = disallow all ; `*` = allow all ; `<google>` = allow just google ; `<google>,<github>` = allow just google or github'; -- {{{SYNC-MAX-AUTH-PLUGIN-ID-LENGTH}}}
COMMENT ON COLUMN web.auth_users.seckey       IS 'Security Key :: TEXT ; Encrypted';
COMMENT ON COLUMN web.auth_users.signkeys     IS 'User Public and Private Sign Keys :: TEXT ; Encrypted';
COMMENT ON COLUMN web.auth_users.priv         IS 'Account Privileges List :: STRING(255) ; <priv1>,<priv2>';
COMMENT ON COLUMN web.auth_users.restr        IS 'Account Restrictions List :: STRING(255) ; <restr1>,<restr2>';
COMMENT ON COLUMN web.auth_users.iprestr      IS 'IP Restrictions List :: STRING(255) ; <ip1>,<ip2>';
COMMENT ON COLUMN web.auth_users.settings     IS 'User Account various Settings :: JSON{} + Index # max 7 levels ; Relations, Accounts, etc...';
COMMENT ON COLUMN web.auth_users.quota        IS 'Account Quota :: BIGINT(-1..INT64.MAX) ; -1 = Unlimited ; 0 = No Quota ; 1..n Quota in MB';
COMMENT ON COLUMN web.auth_users.name         IS 'Name :: STRING(129) ; Ex: John Doe'; -- 64 FName + SPACE + 64 LName
COMMENT ON COLUMN web.auth_users.data         IS 'User Data :: JSON{} + Indexed # max 2 levels only ; ex: country, city, ...';
COMMENT ON COLUMN web.auth_users.jwtserial    IS 'Auth JWT Serial ; STRING(21) ; as UUID.seq-UUID.str ; ex: 0W2H377UFZ-K8E9TWL007';
COMMENT ON COLUMN web.auth_users.jwtsignature IS 'Auth JWT Signature ; STRING(255) ; B64u';
COMMENT ON COLUMN web.auth_users.lastseen     IS 'Account Last SignIn Date and Time: YYYY-MM-DD HH:II:SS';
COMMENT ON COLUMN web.auth_users.ipaddr       IS 'Account Last SignIn IP Address :: IPv4 / IPv6';
COMMENT ON COLUMN web.auth_users.authlog      IS 'Last Auth Data Log :: TEXT';

ALTER TABLE ONLY web.auth_users ADD CONSTRAINT web__auth_users__pkey PRIMARY KEY (id);
ALTER TABLE ONLY web.auth_users ADD CONSTRAINT web__auth_users__key_email UNIQUE (email);

CREATE INDEX web__auth_users__idx_registered   ON web.auth_users USING btree (registered);
CREATE INDEX web__auth_users__idx_status       ON web.auth_users USING btree (status);
CREATE INDEX web__auth_users__idx_cluster      ON web.auth_users USING btree (cluster);
CREATE INDEX web__auth_users__idx_passresetcnt ON web.auth_users USING btree (passresetcnt);
CREATE INDEX web__auth_users__idx_passresetldt ON web.auth_users USING btree (passresetldt);
CREATE INDEX web__auth_users__key_settings     ON web.auth_users USING gin (settings);
CREATE INDEX web__auth_users__idx_quota        ON web.auth_users USING btree (quota);
CREATE INDEX web__auth_users__idx_name         ON web.auth_users USING btree (name);
CREATE INDEX web__auth_users__idx_data         ON web.auth_users USING gin (data);
CREATE INDEX web__auth_users__idx_lastseen     ON web.auth_users USING btree (lastseen);
CREATE INDEX web__auth_users__idx_ipaddr       ON web.auth_users USING btree (ipaddr);

--

COMMIT;

--
-- END #
--
