
-- FTS Init: English (EN), No-Stop-Words :: r.20210526 # smart.framework.v.8.7 ###

CREATE TEXT SEARCH DICTIONARY public.english_stem_nostop(Template = snowball, Language = english);
CREATE TEXT SEARCH CONFIGURATION public.english_nostop ( COPY = pg_catalog.english );
ALTER TEXT SEARCH CONFIGURATION public.english_nostop ALTER MAPPING FOR asciiword, asciihword, hword_asciipart, hword, hword_part, word WITH english_stem_nostop;

-- Sample Index: CREATE INDEX some__idx__fts_field ON table USING gin (to_tsvector('public.english_nostop'::regconfig, field));

-- Sample Query: SELECT * FROM "table" WHERE to_tsvector('english_nostop', "field") @@ plainto_tsquery('english_nostop', $1) ORDER BY ts_rank_cd(to_tsvector('english_nostop', "field"), plainto_tsquery('english_nostop', $1)) DESC; -- $1 => $model->escape_str('term');

--
-- #END
--
