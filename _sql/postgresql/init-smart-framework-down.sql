
-- START :: PostgreSQL Functions and Tables for Smart.Framework :: DOWN :: r.20230922 # smart.framework.v.8.7 ###

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

DROP SCHEMA IF EXISTS smart_runtime CASCADE;

-- General Functions #####

DROP FUNCTION IF EXISTS smart_str_striptags(text) CASCADE;

DROP FUNCTION IF EXISTS smart_str_deaccent(text) CASCADE;

DROP FUNCTION IF EXISTS smart_date_diff(date_start date, date_end date) CASCADE;

DROP FUNCTION IF EXISTS smart_date_period_diff(date_start date, date_end date) CASCADE;

-- Aggregate Functions: FIRST() and LAST()

DROP FUNCTION IF EXISTS smart_agg_first(anyelement, anyelement) CASCADE;

DROP AGGREGATE IF EXISTS agg_smart_first (sfunc, basetype, stype) CASCADE;

DROP FUNCTION IF EXISTS smart_agg_last(anyelement, anyelement) CASCADE;

DROP AGGREGATE IF EXISTS agg_smart_last (sfunc, basetype, stype) CASCADE;

-- JsonB Array Functions #####

DROP FUNCTION IF EXISTS smart_jsonb_arr_delete(data jsonb, rval text) CASCADE;

-- check for duplicates: OK (UNION not UNION ALL)
DROP FUNCTION IF EXISTS smart_jsonb_arr_append(data jsonb, aval jsonb) CASCADE;

-- JsonB Object Functions #####

DROP FUNCTION IF EXISTS smart_jsonb_obj_delete(data jsonb, rkey text) CASCADE;

-- check for duplicates: OK (is UNION ALL but duplicate keys will merge)
DROP FUNCTION IF EXISTS smart_jsonb_obj_append(data jsonb, aobj jsonb) CASCADE;

-- Table _info #####

DROP TABLE IF EXISTS _info CASCADE;

-- COMMIT Transaction #####

COMMIT;

-- END #####
