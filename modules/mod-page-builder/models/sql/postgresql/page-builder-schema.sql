
-- START :: PostgreSQL: Web/PageBuilder :: r.20250107 # smart.framework.v.8.7 ###

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

-- Table Structure: Web.Page-Builder #

CREATE TABLE web.page_builder (
	id character varying(63) NOT NULL,
	ref jsonb DEFAULT '[]'::jsonb NOT NULL,
	ctrl character varying(128) DEFAULT ''::character varying NOT NULL,
	active smallint DEFAULT 0 NOT NULL,
	auth smallint DEFAULT 0 NOT NULL,
	special integer DEFAULT 0 NOT NULL,
	name character varying(255) DEFAULT ''::character varying NOT NULL,
	mode character varying(8) NOT NULL,
	data text DEFAULT ''::text NOT NULL,
	code text DEFAULT ''::text NOT NULL,
	layout character varying(75) DEFAULT ''::character varying NOT NULL,
	tags jsonb DEFAULT '[]'::jsonb NOT NULL,
	checksum character varying(32) DEFAULT ''::character varying NOT NULL,
	translations smallint DEFAULT 0 NOT NULL,
	counter bigint DEFAULT 0 NOT NULL,
	admin character varying(25) DEFAULT ''::character varying NOT NULL,
	published bigint DEFAULT 0 NOT NULL,
	modified character varying(27) DEFAULT ''::character varying NOT NULL,
	CONSTRAINT page_builder__chk__id CHECK ((char_length((id)::text) >= 2)),
	CONSTRAINT page_builder__chk__active CHECK (((active = 0) OR (active = 1))),
	CONSTRAINT page_builder__chk__auth CHECK (((auth = 0) OR (auth = 1))),
	CONSTRAINT page_builder__chk__special CHECK ((special >= 0)),
	CONSTRAINT page_builder__chk__mode CHECK ((char_length((mode)::text) >= 3)),
	CONSTRAINT page_builder__chk__translations CHECK (((translations = 0) OR (translations = 1))),
	CONSTRAINT page_builder__chk__published CHECK ((published >= 0))
);

COMMENT ON TABLE web.page_builder IS 'Web - Page Builder v.2025.01.07';
COMMENT ON COLUMN web.page_builder.id IS 'Unique ID for the Record: Page or Segment (segments must begin with: #)';
COMMENT ON COLUMN web.page_builder.ref IS 'Reference Parent IDs as Json-Array [], Optional';
COMMENT ON COLUMN web.page_builder.ctrl IS 'Parent Controller ID, Optional';
COMMENT ON COLUMN web.page_builder.active IS 'Active Status: 0=inactive ; 1=active';
COMMENT ON COLUMN web.page_builder.auth IS 'Auth Status: 0 = no auth ; 1 = requires auth';
COMMENT ON COLUMN web.page_builder.special IS 'Ranking Special: 0..999999999';
COMMENT ON COLUMN web.page_builder.name IS 'Record Name (for management only)';
COMMENT ON COLUMN web.page_builder.mode IS 'Render Mode: html / markdown / text / raw / settings';
COMMENT ON COLUMN web.page_builder.data IS 'Render Active Runtime (Yaml/B64)';
COMMENT ON COLUMN web.page_builder.code IS 'Render Code (Txt/B64)';
COMMENT ON COLUMN web.page_builder.layout IS 'Page Template (Pages Only) / Segment Area (Segments Only)';
COMMENT ON COLUMN web.page_builder.tags IS 'Tags as Json-Array [], Optional';
COMMENT ON COLUMN web.page_builder.checksum IS 'Checksum (MD5)';
COMMENT ON COLUMN web.page_builder.translations IS 'Allow Translations (1 = yes ; 0 = no)';
COMMENT ON COLUMN web.page_builder.counter IS 'Hit Counter';
COMMENT ON COLUMN web.page_builder.admin IS 'Author';
COMMENT ON COLUMN web.page_builder.published IS 'Time of Publising: timestamp';
COMMENT ON COLUMN web.page_builder.modified IS 'Last Modification: Y-m-d H:i:s';

ALTER TABLE ONLY web.page_builder ADD CONSTRAINT page_builder__id PRIMARY KEY (id);

CREATE INDEX page_builder__idx__ref ON web.page_builder USING gin (ref);
CREATE INDEX page_builder__idx__ctrl ON web.page_builder USING btree (ctrl);
CREATE INDEX page_builder__idx__active ON web.page_builder USING btree (active);
CREATE INDEX page_builder__idx__auth ON web.page_builder USING btree (auth);
CREATE INDEX page_builder__idx__special ON web.page_builder USING btree (special);
CREATE INDEX page_builder__idx__mode ON web.page_builder USING btree (mode);
CREATE INDEX page_builder__idx__tags ON web.page_builder USING gin (tags);
CREATE INDEX page_builder__idx__translations ON web.page_builder USING btree (translations);
CREATE INDEX page_builder__idx__counter ON web.page_builder USING btree (counter);
CREATE INDEX page_builder__idx__admin ON web.page_builder USING btree (admin);
CREATE INDEX page_builder__idx__modified ON web.page_builder USING btree (modified);


-- Table Structure: Web.Page-Builder Translations #

CREATE TABLE web.page_translations (
	id character varying(63) NOT NULL,
	lang character varying(2) NOT NULL,
	code text NOT NULL,
	admin character varying(25) DEFAULT ''::character varying NOT NULL,
	modified character varying(27) DEFAULT ''::character varying NOT NULL,
	CONSTRAINT page_translations__chk__id CHECK ((char_length((id)::text) >= 2)),
	CONSTRAINT page_translations__chk__lang CHECK ((char_length((lang)::text) = 2))
);

ALTER TABLE ONLY web.page_translations ADD CONSTRAINT page_translations_pkey PRIMARY KEY (id, lang);

COMMENT ON TABLE web.page_translations IS 'Web - Page (Builder) Translations v.2025.01.07';
COMMENT ON COLUMN web.page_translations.id IS 'Unique ID for the Record: Page or Segment (segments must begin with: #)';
COMMENT ON COLUMN web.page_translations.lang IS 'Language ID: de, fr, ro, ...';
COMMENT ON COLUMN web.page_translations.code IS 'Render Code (Txt/B64)';
COMMENT ON COLUMN web.page_translations.admin IS 'Author';
COMMENT ON COLUMN web.page_translations.modified IS 'Last Modification: Y-m-d H:i:s';

CREATE INDEX page_translations__idx__id ON web.page_translations USING btree (id);
CREATE INDEX page_translations__idx__lang ON web.page_translations USING btree (lang);
CREATE INDEX page_translations__idx__admin ON web.page_translations USING btree (admin);
CREATE INDEX page_translations__idx__modified ON web.page_translations USING btree (modified);

--

COMMIT;

--
-- END #
--
