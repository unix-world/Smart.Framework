
-- START :: PostgreSQL Functions and Tables for Smart.Framework :: UP :: r.20230922 # smart.framework.v.8.7 ###

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;
SET default_tablespace = '';
SET default_with_oids = false;

-- BEGIN Transaction #####

BEGIN;

-- Smart Runtime Schema

CREATE SCHEMA IF NOT EXISTS smart_runtime;
COMMENT ON SCHEMA smart_runtime IS 'Smart Framework Runtime r.20230922 (do not delete)';

-- General Functions #####

CREATE OR REPLACE FUNCTION smart_str_striptags(text) RETURNS text
	LANGUAGE sql IMMUTABLE STRICT
	AS $_$ -- strip html tags (v.170305)
SELECT COALESCE(
	regexp_replace(
		COALESCE(
			regexp_replace($1, E'(?x)<[^>]*?(\s alt \s* = \s* ([\'"]) ([^>]*?) \2) [^>]*? >', E'\3')
		, '')
	, E'(?x)(< [^>]*? >)', '', 'g')
, '');
$_$;

CREATE OR REPLACE FUNCTION smart_str_deaccent(text) RETURNS text
	LANGUAGE sql IMMUTABLE STRICT
	AS $_$ -- deaccent strings c.176x2 (v.170305)
SELECT COALESCE(
	replace(
		COALESCE(
			translate(
				$1,
				'áâãäåāăąÁÂÃÄÅĀĂĄćĉčçĆĈČÇďĎèéêëēĕėěęÈÉÊËĒĔĖĚĘĝģĜĢĥħĤĦìíîïĩīĭȉȋįÌÍÎÏĨĪĬȈȊĮĳĵĲĴķĶĺļľłĹĻĽŁñńņňÑŃŅŇóôõöōŏőøœÒÓÔÕÖŌŎŐØŒŕŗřŔŖŘșşšśŝšȘŞŠŚŜŠțţťȚŢŤùúûüũūŭůűųÙÚÛÜŨŪŬŮŰŲŵŴẏỳŷÿýẎỲŶŸÝźżžŹŻŽ',
				'aaaaaaaaAAAAAAAAccccCCCCdDeeeeeeeeeEEEEEEEEEggGGhhHHiiiiiiiiiiIIIIIIIIIIjjJJkKllllLLLLnnnnNNNNoooooooooOOOOOOOOOOrrrRRRssssssSSSSSStttTTTuuuuuuuuuuUUUUUUUUUUwWyyyyyYYYYYzzzZZZ'
			)
		, '')
	, 'ß', 'ss')
, '');
$_$;

CREATE OR REPLACE FUNCTION smart_date_diff(date_start date, date_end date) RETURNS bigint
	LANGUAGE sql IMMUTABLE STRICT
	AS $_$ -- return date diff in days (v.190105)
SELECT COALESCE(
	FLOOR(EXTRACT('epoch' FROM ($2::timestamp - $1::timestamp)::interval)::bigint / (3600 * 24)::int)::bigint
, 0)
$_$;

CREATE OR REPLACE FUNCTION smart_date_period_diff(date_start date, date_end date) RETURNS bigint
	LANGUAGE sql IMMUTABLE STRICT
	AS $_$ -- return date period diff in months (v.170325)
SELECT COALESCE(
	((12)::int * EXTRACT('years' FROM age($2::timestamp, $1::timestamp)::interval))::bigint
	+
	((1)::int * EXTRACT('months' FROM age($2::timestamp, $1::timestamp)::interval))::bigint
, 0)
$_$;

-- Aggregate Functions: FIRST() and LAST() :: https://wiki.postgresql.org/wiki/First/last_(aggregate) ; This aggregate functions return the value from the first or last input row in each group, ignoring NULL rows. (NULLs are ignored automatically by the STRICT declaration, documented here: http://www.postgresql.org/docs/current/static/sql-createaggregate.html

CREATE OR REPLACE FUNCTION smart_agg_first(anyelement, anyelement) RETURNS anyelement
	LANGUAGE SQL IMMUTABLE STRICT
	AS $_$ -- Create a function that always returns the first non-NULL item, to be used with GROUP BY agg_smart_first() aggregate (v.170403)
SELECT $1;
$_$;
CREATE OR REPLACE AGGREGATE agg_smart_first (
	sfunc 		= smart_agg_first,
	basetype 	= anyelement,
	stype 		= anyelement
);
COMMENT ON AGGREGATE agg_smart_first(anyelement) IS 'Use this aggregate to return the first non-NULL item on a GROUP BY statement ; needs the smart_agg_first() function (v.170403)';


CREATE OR REPLACE FUNCTION smart_agg_last(anyelement, anyelement) RETURNS anyelement
	LANGUAGE SQL IMMUTABLE STRICT
	AS $_$ -- Create a function that always returns the last non-NULL item, to be used with GROUP BY agg_smart_last() aggregate (v.170403)
SELECT $2;
$_$;
CREATE OR REPLACE AGGREGATE agg_smart_last (
	sfunc 		= smart_agg_last,
	basetype 	= anyelement,
	stype 		= anyelement
);
COMMENT ON AGGREGATE agg_smart_last(anyelement) IS 'Use this aggregate to return the last non-NULL item on a GROUP BY statement ; needs the smart_agg_last() function (v.170403)';

-- JsonB Array Functions #####

CREATE OR REPLACE FUNCTION smart_jsonb_arr_delete(data jsonb, rval text)
RETURNS jsonb
IMMUTABLE
LANGUAGE sql
AS $_$ -- delete by value from jsonb array [] (v.170305)
SELECT COALESCE(
	json_agg(value)::jsonb
, '[]')
FROM (
	SELECT value FROM jsonb_array_elements_text($1) WHERE value != $2
) t;
$_$;

-- check for duplicates: OK (UNION not UNION ALL)
CREATE OR REPLACE FUNCTION smart_jsonb_arr_append(data jsonb, aval jsonb)
RETURNS jsonb
IMMUTABLE
LANGUAGE sql
AS $_$ -- appends a jsonb array [] with another json array [] (v.170305)
SELECT COALESCE(
	json_agg(value)::jsonb
, '[]')
FROM (
	SELECT * FROM jsonb_array_elements_text($1)
	UNION
	SELECT * FROM jsonb_array_elements_text($2)
) t;
$_$;

-- JsonB Object Functions #####

CREATE OR REPLACE FUNCTION smart_jsonb_obj_delete(data jsonb, rkey text)
RETURNS jsonb
IMMUTABLE
LANGUAGE sql
AS $_$ -- delete by key from jsonb object {} (v.170305)
SELECT COALESCE(
	json_object_agg(key, value)::jsonb
, '{}')
FROM (
	SELECT * FROM jsonb_each($1)
	WHERE key != $2
) t;
$_$;

-- check for duplicates: OK (is UNION ALL but duplicate keys will merge)
CREATE OR REPLACE FUNCTION smart_jsonb_obj_append(data jsonb, aobj jsonb)
RETURNS jsonb
IMMUTABLE
LANGUAGE sql
AS $_$ -- appends a jsonb object {} with another json object {} (v.170305)
SELECT COALESCE(
	json_object_agg(key, value)::jsonb
, '{}')
FROM (
	SELECT * FROM jsonb_each($1)
	UNION ALL
	SELECT * FROM jsonb_each($2)
) t;
$_$;

-- Table _info #####

CREATE TABLE _info (
	variable character varying(100) NOT NULL,
	value character varying(16384) DEFAULT ''::character varying NOT NULL,
	comments text DEFAULT ''::text NOT NULL,
	CONSTRAINT _info__check__variable CHECK ((char_length((variable)::text) >= 1))
);
ALTER TABLE ONLY _info ADD CONSTRAINT _info__variable PRIMARY KEY (variable);
COMMENT ON TABLE _info IS 'Smart.Framework MetaInfo v.2023.09.22';
COMMENT ON COLUMN _info.variable IS 'The Variable';
COMMENT ON COLUMN _info.value IS 'The Value';
COMMENT ON COLUMN _info.comments IS 'The Comments';
INSERT INTO _info VALUES ('version', 'smart.framework', 'Software version to Validate DB');
INSERT INTO _info VALUES ('id', 'app.default', 'The Unique ID of application.
Example:
''some.id''
This will avoid to accidental connect to other database.');
INSERT INTO _info VALUES ('history', '', 'Record Upgrades History');

-- COMMIT Transaction #####

COMMIT;

-- END #####
