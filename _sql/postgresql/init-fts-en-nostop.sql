
-- PostgreSQL FTS :: r.20221212 # smart.framework.v.8.7 ###


-- FTS Init: English (EN), No-Stop-Words (requires to create a custom dictionary)

CREATE TEXT SEARCH DICTIONARY public.english_stem_nostop(Template = snowball, Language = english);
CREATE TEXT SEARCH CONFIGURATION public.english_nostop ( COPY = pg_catalog.english );
ALTER TEXT SEARCH CONFIGURATION public.english_nostop ALTER MAPPING FOR asciiword, asciihword, hword_asciipart, hword, hword_part, word WITH english_stem_nostop;

-- Sample Index: CREATE INDEX some__idx__fts_field ON tbl USING gin (to_tsvector('public.english_nostop'::regconfig, field));
-- Sample Query by Index: SELECT * FROM "tbl" WHERE to_tsvector('english_nostop', "field") @@ plainto_tsquery('english_nostop', $1) ORDER BY ts_rank_cd(to_tsvector('english_nostop', "field"), plainto_tsquery('english_nostop', $1)) DESC; -- $1 => $model->escape_str('term');


-- FTS Init: English (EN), Default (uses default dictionary)

-- Sample MultiIndex (req. creating a composed but also separate indexes in order to avoid SEQ. Scan and use FTS Index:
--		CREATE INDEX trips__fts ON trips USING gin ( ( to_tsvector('english', title) || to_tsvector('english', description) || to_tsvector('english', location) ) );
--		CREATE INDEX trips__fts__title ON trips USING gist ( to_tsvector('english', title) );
--		CREATE INDEX trips__fts__description ON trips USING gist ( to_tsvector('english', description) );
--		CREATE INDEX trips__fts__location ON trips USING gist ( to_tsvector('english', location) );
-- Sample Query by Multi_index:
--		EXPLAIN SELECT * FROM trips WHERE (
--			(
--				to_tsvector('english', title) ||
--				to_tsvector('english', description) ||
--				to_tsvector('english', location)
--			) @@ plainto_tsquery('english', 'Search Expression')
--		);


--
-- #END
--
