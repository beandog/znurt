--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: znurt; Type: DATABASE; Schema: -; Owner: -
--

CREATE DATABASE znurt WITH TEMPLATE = template0 ENCODING = 'UTF8' LC_COLLATE = 'en_US.UTF-8' LC_CTYPE = 'en_US.UTF-8';


\connect znurt

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- Name: category_id(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.category_id(atom character varying, OUT i integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
	DECLARE c varchar;
BEGIN
	c := category_name(atom);
	i := id FROM category WHERE name = c;
END;
$$;


--
-- Name: category_name(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.category_name(str character varying, OUT v character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$ BEGIN IF POSITION('/' IN str) > 0 THEN
v := regexp_replace(str, E'^(!{1,2})?(>|<)?(~|=)?', '');
v := regexp_replace(v, E'/.*', '');
ELSE
v := str;
END IF;
END;
$$;


--
-- Name: package_description(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.package_description(package_id integer, OUT package_description text) RETURNS text
    LANGUAGE plpgsql
    AS $$
BEGIN

package_description := em.value FROM ebuild e INNER JOIN package p ON e.package = p.id AND e.package = package_id INNER JOIN ebuild_metadata em ON em.ebuild = e.id AND em.keyword::text = 'description'::text WHERE e.id = (( SELECT e2.id FROM ebuild e2 WHERE e2.package = package_id ORDER BY e2.cache_mtime DESC, e2.ev DESC,  e2.lvl DESC, e2.p IS NULL, e2.p DESC, e2.rc IS NULL, e2.rc DESC, e2.pre IS NULL, e2.pre DESC, e2.beta IS NULL, e2.beta DESC, e2.alpha IS NULL, e2.alpha DESC, e2.pr IS NULL, e2.pr DESC LIMIT 1));

END;
$$;


--
-- Name: package_id(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.package_id(ebuild_id integer, OUT id integer) RETURNS integer
    LANGUAGE sql
    AS $_$ SELECT package FROM ebuild WHERE id = $1 $_$;


--
-- Name: package_id(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.package_id(atom character varying, OUT i integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
DECLARE
        c integer;
        p varchar;
BEGIN
        c := category_id(atom);
        p := package_name(atom);
        i := id FROM package WHERE category = c AND name = p;
END;
$$;


--
-- Name: package_id2(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.package_id2(atom character varying, OUT i integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
        DECLARE p varchar;
BEGIN
        p := package_name(atom);
        i := id FROM package WHERE name = p;
END;
$$;


--
-- Name: package_name(character varying); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.package_name(str character varying, OUT package_name character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $_$
BEGIN

package_name := str;

IF POSITION('/' IN str) > 0 THEN
package_name := regexp_replace(package_name, E'^(!{1,2})?(>|<)?(~|=)?.*/', '');
END IF;

IF POSITION('*' IN package_name) > 0 THEN
package_name := regexp_replace(package_name, E'\\*.*$', '');
END IF;

IF POSITION(':' IN package_name) > 0 THEN
package_name := regexp_replace(package_name, E':.+$', '');
END IF;

IF POSITION('[' IN package_name) > 0 THEN
package_name := regexp_replace(package_name, E'\\[.+$', '');
END IF;


package_name := regexp_replace(package_name, E'-\\d+((\.\\d+)+)?([a-z])?((_(alpha|beta|pre|rc|p)\\d*)+)?(-r\\d+)?(:.+)?([.+])?$', '');
END;
$_$;


--
-- Name: truncate_ebuild_tables(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.truncate_ebuild_tables() RETURNS void
    LANGUAGE sql
    AS $$ TRUNCATE ebuild_arch; TRUNCATE ebuild_eclass; TRUNCATE ebuild_homepage; TRUNCATE ebuild_license; TRUNCATE ebuild_use; $$;


SET default_tablespace = '';

SET default_with_oids = true;

--
-- Name: arch; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.arch (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    active boolean DEFAULT false NOT NULL,
    prefix boolean DEFAULT false NOT NULL
);


--
-- Name: arch_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.arch_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: arch_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.arch_id_seq OWNED BY public.arch.id;


SET default_with_oids = false;

--
-- Name: bugzilla; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.bugzilla (
    bug_id integer NOT NULL,
    bug_severity character varying(255) DEFAULT ''::character varying NOT NULL,
    priority character varying(12) DEFAULT ''::character varying NOT NULL,
    op_sys character varying(255) DEFAULT ''::character varying NOT NULL,
    assigned_to character varying(255) DEFAULT ''::character varying NOT NULL,
    bug_status character varying(255) DEFAULT ''::character varying NOT NULL,
    resolution character varying(255) DEFAULT ''::character varying,
    short_short_desc character varying(255) DEFAULT ''::character varying NOT NULL
);


SET default_with_oids = true;

--
-- Name: category; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.category (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    description character varying(255) DEFAULT ''::character varying NOT NULL
);


SET default_with_oids = false;

--
-- Name: category_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.category_description (
    category integer NOT NULL,
    lingua character(2) DEFAULT 'en'::bpchar NOT NULL,
    description text DEFAULT ''::text NOT NULL
);


--
-- Name: category_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.category_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: category_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.category_id_seq OWNED BY public.category.id;


--
-- Name: ebuild; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild (
    id integer NOT NULL,
    package integer NOT NULL,
    pf character varying(255) DEFAULT ''::character varying NOT NULL,
    pv character varying(255) DEFAULT ''::character varying NOT NULL,
    pr integer,
    pvr character varying(255) DEFAULT ''::character varying NOT NULL,
    alpha character varying(255),
    beta character varying(255),
    pre character varying(255),
    rc character varying(255),
    p character varying(255),
    version character varying(255) DEFAULT ''::character varying NOT NULL,
    slot character varying(255) DEFAULT '0'::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    ev character varying DEFAULT ''::character varying NOT NULL,
    lvl smallint DEFAULT 0 NOT NULL,
    udate timestamp with time zone,
    portage_mtime bigint,
    cache_mtime bigint,
    source text DEFAULT ''::text NOT NULL,
    filesize integer DEFAULT 0 NOT NULL,
    hash character(40) DEFAULT ''::bpchar NOT NULL
);


--
-- Name: COLUMN ebuild.status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.ebuild.status IS 'complete, new or updated, remove';


SET default_with_oids = true;

--
-- Name: ebuild_arch; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_arch (
    ebuild integer NOT NULL,
    arch integer NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: COLUMN ebuild_arch.status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.ebuild_arch.status IS 'stable, unstable, no workie';


SET default_with_oids = false;

--
-- Name: ebuild_depend; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_depend (
    ebuild integer NOT NULL,
    package integer NOT NULL,
    type character varying(7) DEFAULT ''::character varying NOT NULL
);


SET default_with_oids = true;

--
-- Name: ebuild_eclass; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_eclass (
    ebuild integer NOT NULL,
    eclass integer NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ebuild_homepage; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_homepage (
    ebuild integer NOT NULL,
    homepage text DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ebuild_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ebuild_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ebuild_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ebuild_id_seq OWNED BY public.ebuild.id;


--
-- Name: ebuild_license; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_license (
    ebuild integer NOT NULL,
    license smallint NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


SET default_with_oids = false;

--
-- Name: ebuild_mask; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_mask (
    package_mask integer,
    ebuild integer,
    status smallint DEFAULT 0 NOT NULL
);


--
-- Name: ebuild_metadata; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_metadata (
    ebuild integer NOT NULL,
    keyword character varying(255) DEFAULT ''::character varying NOT NULL,
    value text DEFAULT ''::text NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


SET default_with_oids = true;

--
-- Name: ebuild_use; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_use (
    ebuild integer NOT NULL,
    use integer NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


SET default_with_oids = false;

--
-- Name: ebuild_version; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.ebuild_version (
    ebuild integer NOT NULL,
    version character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


SET default_with_oids = true;

--
-- Name: package; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package (
    id integer NOT NULL,
    category integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    description text DEFAULT ''::text,
    status smallint DEFAULT 0 NOT NULL,
    portage_mtime bigint,
    manifest_hash character varying(32) DEFAULT ''::character varying NOT NULL
);


--
-- Name: COLUMN package.status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.package.status IS 'normal, portage_mtime changed';


--
-- Name: ebuilds; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.ebuilds AS
 SELECT e.id,
    c.name AS category_name,
    c.id AS category,
    p.name AS package_name,
    e.package,
    e.pf,
    e.pv,
    e.pr,
    e.pvr,
    e.alpha,
    e.beta,
    e.pre,
    e.rc,
    e.p,
    e.slot,
    e.version,
    e.ev,
    e.lvl,
    e.cache_mtime,
    e.idate,
    (em.ebuild IS NOT NULL) AS masked,
    e.udate
   FROM (((public.ebuild e
     JOIN public.package p ON ((e.package = p.id)))
     JOIN public.category c ON ((c.id = p.category)))
     LEFT JOIN public.ebuild_mask em ON (((e.id = em.ebuild) AND (em.status = 0))))
  WHERE (e.status = ANY (ARRAY[0, 2]))
  ORDER BY c.name, p.name, e.ev DESC, e.lvl DESC, (e.p IS NULL), e.p DESC, (e.rc IS NULL), e.rc DESC, (e.pre IS NULL), e.pre DESC, (e.beta IS NULL), e.beta DESC, (e.alpha IS NULL), e.alpha DESC, (e.pr IS NULL), e.pr DESC;


--
-- Name: eclass; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.eclass (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: eclass_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.eclass_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: eclass_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.eclass_id_seq OWNED BY public.eclass.id;


SET default_with_oids = false;

--
-- Name: import_status; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.import_status (
    id integer NOT NULL,
    status character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    udate timestamp without time zone DEFAULT now()
);


--
-- Name: import_status_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.import_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: import_status_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.import_status_id_seq OWNED BY public.import_status.id;


SET default_with_oids = true;

--
-- Name: license; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.license (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: license_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.license_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: license_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.license_id_seq OWNED BY public.license.id;


SET default_with_oids = false;

--
-- Name: meta; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.meta (
    keyword character varying(255) DEFAULT ''::character varying NOT NULL,
    value character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: missing_arch; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.missing_arch AS
 SELECT e.id AS ebuild,
    c.name AS category_name,
    p.name AS package_name,
    e.pf,
    em.value AS metadata
   FROM ((((public.category c
     JOIN public.package p ON ((p.category = c.id)))
     JOIN public.ebuild e ON ((e.package = p.id)))
     LEFT JOIN public.ebuild_arch ea ON ((ea.ebuild = e.id)))
     LEFT JOIN public.ebuild_metadata em ON (((em.ebuild = e.id) AND ((em.keyword)::text = 'keywords'::text))))
  WHERE ((ea.ebuild IS NULL) AND (em.value <> ''::text));


--
-- Name: missing_depend; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.missing_depend AS
 SELECT e.id,
    c.name AS category_name,
    p.name AS package_name,
    em.keyword AS type,
    em.value AS metadata
   FROM ((((public.category c
     JOIN public.package p ON ((c.id = p.category)))
     JOIN public.ebuild e ON ((e.package = p.id)))
     LEFT JOIN public.ebuild_depend ed ON ((ed.ebuild = e.id)))
     LEFT JOIN public.ebuild_metadata em ON (((em.ebuild = e.id) AND ((em.keyword)::text = ANY (ARRAY[('depend'::character varying)::text, ('rdepend'::character varying)::text])))))
  WHERE ((ed.ebuild IS NULL) AND (em.value <> ''::text));


--
-- Name: missing_ev; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.missing_ev AS
 SELECT DISTINCT e1.package,
    e2.id AS ebuild,
    e2.version
   FROM (public.ebuild e1
     LEFT JOIN public.ebuild e2 ON ((e2.package = e1.package)))
  WHERE ((e1.status = 2) OR ((e1.ev)::text = ''::text))
  ORDER BY e1.package;


--
-- Name: missing_homepage; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.missing_homepage AS
 SELECT e.id AS ebuild,
    c.name AS category_name,
    p.name AS package_name,
    e.pf,
    em.value AS metadata
   FROM ((((public.category c
     JOIN public.package p ON ((p.category = c.id)))
     JOIN public.ebuild e ON ((e.package = p.id)))
     LEFT JOIN public.ebuild_homepage eh ON ((eh.ebuild = e.id)))
     LEFT JOIN public.ebuild_metadata em ON (((em.ebuild = e.id) AND ((em.keyword)::text = 'homepage'::text))))
  WHERE ((eh.ebuild IS NULL) AND (em.value <> ''::text));


--
-- Name: missing_license; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.missing_license AS
 SELECT e.id AS ebuild,
    c.name AS category_name,
    p.name AS package_name,
    e.pf,
    em.value AS metadata
   FROM ((((public.category c
     JOIN public.package p ON ((p.category = c.id)))
     JOIN public.ebuild e ON ((e.package = p.id)))
     LEFT JOIN public.ebuild_license el ON ((el.ebuild = e.id)))
     LEFT JOIN public.ebuild_metadata em ON (((em.ebuild = e.id) AND ((em.keyword)::text = 'license'::text))))
  WHERE ((el.ebuild IS NULL) AND (em.value <> ''::text));


--
-- Name: missing_metadata; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.missing_metadata AS
 SELECT DISTINCT e.id AS ebuild,
    c.name AS category_name,
    p.name AS package_name,
    e.pf
   FROM (((public.category c
     JOIN public.package p ON ((p.category = c.id)))
     JOIN public.ebuild e ON ((e.package = p.id)))
     LEFT JOIN public.ebuild_metadata em ON ((em.ebuild = e.id)))
  WHERE (em.ebuild IS NULL);


--
-- Name: missing_use; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.missing_use AS
 SELECT e.id,
    c.name AS category,
    p.name AS package,
    e.pf,
    em.value AS metadata
   FROM ((((public.category c
     JOIN public.package p ON ((p.category = c.id)))
     JOIN public.ebuild e ON ((e.package = p.id)))
     LEFT JOIN public.ebuild_use eu ON ((eu.ebuild = e.id)))
     LEFT JOIN public.ebuild_metadata em ON (((em.ebuild = e.id) AND ((em.keyword)::text = 'iuse'::text))))
  WHERE ((eu.ebuild IS NULL) AND (em.value <> ''::text));


--
-- Name: mtime; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.mtime (
    filename character varying(255) DEFAULT ''::character varying NOT NULL,
    mtime bigint,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    udate timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: new_packages; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.new_packages AS
 SELECT p.id AS package,
    c.name AS category_name,
    p.name AS package_name,
    p.portage_mtime
   FROM (public.category c
     JOIN public.package p ON ((p.category = c.id)))
  WHERE (p.portage_mtime IS NOT NULL)
  ORDER BY p.idate DESC, c.name, p.name;


--
-- Name: package_bugs; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_bugs (
    bug integer NOT NULL,
    description character varying(255) DEFAULT ''::character varying NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    package integer NOT NULL,
    idate timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: COLUMN package_bugs.status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.package_bugs.status IS 'complete, new';


--
-- Name: package_changelog; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_changelog (
    package integer NOT NULL,
    changelog text DEFAULT ''::text NOT NULL,
    mtime bigint NOT NULL,
    hash character(40) DEFAULT ''::bpchar NOT NULL,
    filesize integer NOT NULL,
    recent_changes text DEFAULT ''::text NOT NULL
);


--
-- Name: package_files; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_files (
    id integer NOT NULL,
    package integer NOT NULL,
    filename character varying(255) DEFAULT ''::character varying NOT NULL,
    type character varying(12) DEFAULT ''::character varying NOT NULL,
    hash character varying(255) DEFAULT ''::bpchar,
    filesize bigint NOT NULL
);


--
-- Name: package_files_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.package_files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: package_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.package_files_id_seq OWNED BY public.package_files.id;


--
-- Name: package_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.package_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: package_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.package_id_seq OWNED BY public.package.id;


SET default_with_oids = true;

--
-- Name: package_maintainer; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_maintainer (
    package integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    email character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


SET default_with_oids = false;

--
-- Name: package_manifest; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_manifest (
    package integer NOT NULL,
    manifest text DEFAULT ''::text NOT NULL,
    mtime bigint NOT NULL,
    hash character(40) DEFAULT ''::bpchar NOT NULL,
    filesize integer NOT NULL
);


--
-- Name: package_mask_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.package_mask_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: package_mask; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_mask (
    id integer DEFAULT nextval('public.package_mask_id_seq'::regclass) NOT NULL,
    package integer NOT NULL,
    atom text DEFAULT ''::text NOT NULL,
    lt boolean DEFAULT false NOT NULL,
    gt boolean DEFAULT false NOT NULL,
    eq boolean DEFAULT false NOT NULL,
    ar boolean DEFAULT false NOT NULL,
    av boolean DEFAULT false NOT NULL,
    pf character varying(255) DEFAULT ''::character varying NOT NULL,
    pv character varying(255) DEFAULT ''::character varying NOT NULL,
    pr integer,
    pvr character varying(255) DEFAULT ''::character varying NOT NULL,
    alpha character varying(255),
    beta character varying(255),
    pre character varying(255),
    rc character varying(255),
    p character varying(255),
    version character varying(255) DEFAULT ''::character varying NOT NULL,
    status smallint DEFAULT 0 NOT NULL
);


--
-- Name: package_recent; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_recent (
    package integer,
    max_ebuild_mtime bigint,
    status smallint DEFAULT 0 NOT NULL
);


--
-- Name: package_recent_arch; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_recent_arch (
    package integer,
    max_ebuild_mtime bigint,
    status smallint DEFAULT 0 NOT NULL,
    arch integer
);


--
-- Name: package_use; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.package_use (
    id integer NOT NULL,
    package integer NOT NULL,
    use integer NOT NULL,
    description text DEFAULT ''::text NOT NULL
);


--
-- Name: package_use_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.package_use_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: package_use_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.package_use_id_seq OWNED BY public.package_use.id;


--
-- Name: search_ebuilds; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.search_ebuilds AS
 SELECT e.id AS ebuild,
    e.package,
    c.name AS category_name,
    p.name AS package_name,
    (((c.name)::text || '/'::text) || (p.name)::text) AS cp,
    e.pf AS ebuild_name,
    p.description,
    e.ev,
    e.lvl,
    e.p,
    e.rc,
    e.pre,
    e.beta,
    e.alpha,
    e.pr,
    (((c.name)::text || '/'::text) || (e.pf)::text) AS atom
   FROM (((public.ebuild e
     JOIN public.package p ON ((e.package = p.id)))
     JOIN public.category c ON ((c.id = p.category)))
     LEFT JOIN public.ebuild_mask em ON ((e.id = em.ebuild)))
  WHERE (e.status = ANY (ARRAY[0, 3]));


SET default_with_oids = true;

--
-- Name: use; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.use (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    description text DEFAULT ''::text NOT NULL,
    prefix character varying(255) DEFAULT ''::character varying NOT NULL
);


--
-- Name: use_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.use_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: use_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.use_id_seq OWNED BY public.use.id;


--
-- Name: view_arches; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_arches AS
 SELECT e.id AS ebuild,
    ea.arch,
    a.name,
    ea.status
   FROM ((public.ebuild e
     JOIN public.ebuild_arch ea ON ((ea.ebuild = e.id)))
     JOIN public.arch a ON ((ea.arch = a.id)));


--
-- Name: view_ebuild; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_ebuild AS
 SELECT e.id,
    c.name AS category_name,
    c.id AS category,
    p.name AS package_name,
    e.package,
    e.pf,
    e.pv,
    e.pr,
    e.pvr,
    e.alpha,
    e.beta,
    e.pre,
    e.rc,
    e.p,
    e.slot,
    e.version,
    e.ev,
    e.lvl,
    e.cache_mtime,
    e.portage_mtime,
    e.idate,
    (em.ebuild IS NOT NULL) AS masked
   FROM (((public.ebuild e
     JOIN public.package p ON ((e.package = p.id)))
     JOIN public.category c ON ((c.id = p.category)))
     LEFT JOIN public.ebuild_mask em ON ((e.id = em.ebuild)));


--
-- Name: view_ebuild_depend; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_ebuild_depend AS
 SELECT DISTINCT ed.ebuild,
    (((c.name)::text || '/'::text) || (p.name)::text) AS cp,
    p.description,
    ed.type
   FROM (((public.ebuild_depend ed
     JOIN public.ebuild e ON ((ed.ebuild = e.id)))
     JOIN public.package p ON ((p.id = ed.package)))
     JOIN public.category c ON ((p.category = c.id)));


--
-- Name: view_ebuild_level; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_ebuild_level AS
 SELECT e.id,
        CASE
            WHEN (e.p IS NOT NULL) THEN 6
            WHEN (e.rc IS NOT NULL) THEN 4
            WHEN (e.pre IS NOT NULL) THEN 3
            WHEN (e.beta IS NOT NULL) THEN 2
            WHEN (e.alpha IS NOT NULL) THEN 1
            ELSE 5
        END AS level
   FROM public.ebuild e;


--
-- Name: view_ebuild_use; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_ebuild_use AS
 SELECT e.id AS ebuild,
    u.name,
    COALESCE(pu.description, u.description) AS description
   FROM (((public.use u
     JOIN public.ebuild_use eu ON ((eu.use = u.id)))
     JOIN public.ebuild e ON ((eu.ebuild = e.id)))
     LEFT JOIN public.package_use pu ON (((e.package = pu.package) AND (pu.use = u.id))));


--
-- Name: view_licenses; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_licenses AS
 SELECT e.package,
    e.id AS ebuild,
    el.license,
    l.name
   FROM ((public.ebuild e
     JOIN public.ebuild_license el ON ((el.ebuild = e.id)))
     JOIN public.license l ON ((el.license = l.id)));


--
-- Name: view_package; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_package AS
 SELECT c.id AS category,
    p.id AS package,
    c.name AS category_name,
    p.name AS package_name,
    (((c.name)::text || '/'::text) || (p.name)::text) AS cp
   FROM (public.category c
     JOIN public.package p ON ((p.category = c.id)))
  ORDER BY (((c.name)::text || '/'::text) || (p.name)::text);


--
-- Name: view_package_bugs; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_package_bugs AS
 SELECT b.bug_id AS bug,
    p.id AS package,
    b.short_short_desc
   FROM ((public.package p
     JOIN public.category c ON ((p.category = c.id)))
     JOIN public.bugzilla b ON (((b.short_short_desc)::text ~~ (((('%'::text || (c.name)::text) || '/'::text) || (p.name)::text) || '%'::text))));


--
-- Name: view_package_depend; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_package_depend AS
 SELECT DISTINCT e.package,
    (((c.name)::text || '/'::text) || (p.name)::text) AS cp,
    p.description,
    ed.type
   FROM (((public.ebuild_depend ed
     JOIN public.ebuild e ON ((ed.ebuild = e.id)))
     JOIN public.package p ON ((p.id = ed.package)))
     JOIN public.category c ON ((p.category = c.id)));


--
-- Name: view_packages; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_packages AS
 SELECT c.id AS category,
    p.id AS package,
    c.name AS category_name,
    p.name AS package_name,
    (((c.name)::text || '/'::text) || (p.name)::text) AS cp,
    p.description
   FROM (public.category c
     JOIN public.package p ON ((p.category = c.id)));


--
-- Name: view_package_licenses; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_package_licenses AS
 SELECT DISTINCT l.id AS license,
    l.name AS license_name,
    p.category,
    p.category_name,
    p.package,
    p.package_name,
    p.description
   FROM (((public.view_packages p
     JOIN public.ebuild e ON ((e.package = p.package)))
     JOIN public.ebuild_license el ON ((el.ebuild = e.id)))
     JOIN public.license l ON ((el.license = l.id)));


--
-- Name: view_package_manifest; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_package_manifest AS
 SELECT c.id AS category,
    p.id AS package,
    c.name AS category_name,
    p.name AS package_name,
    (((c.name)::text || '/'::text) || (p.name)::text) AS cp,
    p.manifest_hash
   FROM (public.category c
     JOIN public.package p ON ((p.category = c.id)))
  ORDER BY (((c.name)::text || '/'::text) || (p.name)::text);


--
-- Name: view_package_use; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_package_use AS
 SELECT DISTINCT e.package,
    u.name,
    COALESCE(pu.description, u.description) AS description
   FROM (((public.use u
     JOIN public.ebuild_use eu ON ((eu.use = u.id)))
     JOIN public.ebuild e ON ((eu.ebuild = e.id)))
     LEFT JOIN public.package_use pu ON (((e.package = pu.package) AND (pu.use = u.id))));


--
-- Name: view_package_useflags; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_package_useflags AS
 SELECT DISTINCT u.id AS use,
    u.name AS useflag_name,
    p.category,
    p.category_name,
    p.package,
    p.package_name,
    p.description
   FROM (((public.view_packages p
     JOIN public.ebuild e ON ((e.package = p.package)))
     JOIN public.ebuild_use eu ON ((eu.ebuild = e.id)))
     JOIN public.use u ON ((eu.use = u.id)));


--
-- Name: view_pmask_level; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_pmask_level AS
 SELECT pm.id,
        CASE
            WHEN (pm.p IS NOT NULL) THEN 6
            WHEN (pm.rc IS NOT NULL) THEN 4
            WHEN (pm.pre IS NOT NULL) THEN 3
            WHEN (pm.beta IS NOT NULL) THEN 2
            WHEN (pm.alpha IS NOT NULL) THEN 1
            ELSE 5
        END AS level
   FROM public.package_mask pm;


--
-- Name: view_reverse_depend; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.view_reverse_depend AS
 SELECT DISTINCT ed.ebuild,
    ed.package,
    (((c.name)::text || '/'::text) || (p.name)::text) AS cp,
    p.description,
    c.name AS category_name,
    p.name AS package_name
   FROM (((public.ebuild_depend ed
     JOIN public.ebuild e ON ((ed.ebuild = e.id)))
     JOIN public.package p ON ((e.package = p.id)))
     JOIN public.category c ON ((c.id = p.category)));


SET default_with_oids = false;

--
-- Name: znurt; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE public.znurt (
    id integer NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    action character varying(255) DEFAULT ''::character varying NOT NULL
);


--
-- Name: znurt_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.znurt_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: znurt_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.znurt_id_seq OWNED BY public.znurt.id;


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.arch ALTER COLUMN id SET DEFAULT nextval('public.arch_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category ALTER COLUMN id SET DEFAULT nextval('public.category_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild ALTER COLUMN id SET DEFAULT nextval('public.ebuild_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eclass ALTER COLUMN id SET DEFAULT nextval('public.eclass_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_status ALTER COLUMN id SET DEFAULT nextval('public.import_status_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.license ALTER COLUMN id SET DEFAULT nextval('public.license_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package ALTER COLUMN id SET DEFAULT nextval('public.package_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_files ALTER COLUMN id SET DEFAULT nextval('public.package_files_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_use ALTER COLUMN id SET DEFAULT nextval('public.package_use_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.use ALTER COLUMN id SET DEFAULT nextval('public.use_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.znurt ALTER COLUMN id SET DEFAULT nextval('public.znurt_id_seq'::regclass);


--
-- Name: category_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.category
    ADD CONSTRAINT category_pkey PRIMARY KEY (id);

ALTER TABLE public.category CLUSTER ON category_pkey;


--
-- Name: ebuild_metadata_ebuild_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.ebuild_metadata
    ADD CONSTRAINT ebuild_metadata_ebuild_key UNIQUE (ebuild, keyword);


--
-- Name: ebuild_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.ebuild
    ADD CONSTRAINT ebuild_pkey PRIMARY KEY (id);

ALTER TABLE public.ebuild CLUSTER ON ebuild_pkey;


--
-- Name: import_status_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.import_status
    ADD CONSTRAINT import_status_pkey PRIMARY KEY (id);


--
-- Name: mtime_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.mtime
    ADD CONSTRAINT mtime_pkey PRIMARY KEY (filename);


--
-- Name: package_mask_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.package_mask
    ADD CONSTRAINT package_mask_pkey PRIMARY KEY (id);


--
-- Name: package_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.package
    ADD CONSTRAINT package_pkey PRIMARY KEY (id);

ALTER TABLE public.package CLUSTER ON package_pkey;


--
-- Name: package_use_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.package_use
    ADD CONSTRAINT package_use_pkey PRIMARY KEY (id);


--
-- Name: pkey_arch; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.arch
    ADD CONSTRAINT pkey_arch PRIMARY KEY (id);


--
-- Name: pkey_ebuild_use; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.ebuild_use
    ADD CONSTRAINT pkey_ebuild_use PRIMARY KEY (ebuild, use);


--
-- Name: pkey_eclass; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.eclass
    ADD CONSTRAINT pkey_eclass PRIMARY KEY (id);


--
-- Name: pkey_license; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.license
    ADD CONSTRAINT pkey_license PRIMARY KEY (id);


--
-- Name: uniq_arch; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.arch
    ADD CONSTRAINT uniq_arch UNIQUE (name);


--
-- Name: uniq_category_name; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.category
    ADD CONSTRAINT uniq_category_name UNIQUE (name);


--
-- Name: uniq_ebuild_arch; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.ebuild_arch
    ADD CONSTRAINT uniq_ebuild_arch UNIQUE (ebuild, arch);


--
-- Name: uniq_ebuild_eclass; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.ebuild_eclass
    ADD CONSTRAINT uniq_ebuild_eclass UNIQUE (ebuild, eclass);


--
-- Name: uniq_ebuild_homepage; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.ebuild_homepage
    ADD CONSTRAINT uniq_ebuild_homepage UNIQUE (ebuild, homepage);


--
-- Name: uniq_ebuild_license; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.ebuild_license
    ADD CONSTRAINT uniq_ebuild_license UNIQUE (ebuild, license);


--
-- Name: uniq_eclass_name; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.eclass
    ADD CONSTRAINT uniq_eclass_name UNIQUE (name);


--
-- Name: uniq_license_name; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.license
    ADD CONSTRAINT uniq_license_name UNIQUE (name);


--
-- Name: use_name_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.use
    ADD CONSTRAINT use_name_key UNIQUE (name);


--
-- Name: use_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.use
    ADD CONSTRAINT use_pkey PRIMARY KEY (id);


--
-- Name: znurt_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY public.znurt
    ADD CONSTRAINT znurt_pkey PRIMARY KEY (id);


--
-- Name: idx_bugzilla_bug; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_bugzilla_bug ON public.package_bugs USING btree (bug);


--
-- Name: idx_bugzilla_description; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_bugzilla_description ON public.bugzilla USING btree (short_short_desc);


--
-- Name: idx_bugzilla_description_txt; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_bugzilla_description_txt ON public.bugzilla USING btree (short_short_desc text_pattern_ops);


--
-- Name: idx_category_name_txt; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_category_name_txt ON public.category USING btree (name text_pattern_ops);


--
-- Name: idx_ebuild_pf_txt; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_ebuild_pf_txt ON public.ebuild USING btree (pf text_pattern_ops);


--
-- Name: idx_package_name; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_package_name ON public.package USING btree (name);


--
-- Name: idx_package_name_txt; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_package_name_txt ON public.package USING btree (name text_pattern_ops);


--
-- Name: idx_use_name; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_use_name ON public.use USING btree (name);


--
-- Name: uniq_cat_name; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE UNIQUE INDEX uniq_cat_name ON public.category USING btree (name);


--
-- Name: uniq_package_name; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE UNIQUE INDEX uniq_package_name ON public.package USING btree (category, name);


--
-- Name: ebuild_arch_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_arch
    ADD CONSTRAINT ebuild_arch_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_depend_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_depend
    ADD CONSTRAINT ebuild_depend_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_depend_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_depend
    ADD CONSTRAINT ebuild_depend_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: ebuild_eclass_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_eclass
    ADD CONSTRAINT ebuild_eclass_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_homepage_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_homepage
    ADD CONSTRAINT ebuild_homepage_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_license_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_license
    ADD CONSTRAINT ebuild_license_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_mask_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_mask
    ADD CONSTRAINT ebuild_mask_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_mask_package_mask_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_mask
    ADD CONSTRAINT ebuild_mask_package_mask_fkey FOREIGN KEY (package_mask) REFERENCES public.package_mask(id) ON DELETE CASCADE;


--
-- Name: ebuild_metadata_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_metadata
    ADD CONSTRAINT ebuild_metadata_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild
    ADD CONSTRAINT ebuild_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: ebuild_use_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_use
    ADD CONSTRAINT ebuild_use_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_version_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_version
    ADD CONSTRAINT ebuild_version_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES public.ebuild(id) ON DELETE CASCADE;


--
-- Name: fkey_ebuild_arch_arch; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_arch
    ADD CONSTRAINT fkey_ebuild_arch_arch FOREIGN KEY (arch) REFERENCES public.arch(id) ON DELETE CASCADE;


--
-- Name: fkey_ebuild_eclass_eclass; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_eclass
    ADD CONSTRAINT fkey_ebuild_eclass_eclass FOREIGN KEY (eclass) REFERENCES public.eclass(id) ON DELETE CASCADE;


--
-- Name: fkey_ebuild_license_license; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_license
    ADD CONSTRAINT fkey_ebuild_license_license FOREIGN KEY (license) REFERENCES public.license(id) ON DELETE CASCADE;


--
-- Name: fkey_ebuild_use_use; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ebuild_use
    ADD CONSTRAINT fkey_ebuild_use_use FOREIGN KEY (use) REFERENCES public.use(id) ON DELETE CASCADE;


--
-- Name: fkey_package_maintainer_package; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_maintainer
    ADD CONSTRAINT fkey_package_maintainer_package FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_bugs_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_bugs
    ADD CONSTRAINT package_bugs_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_category_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package
    ADD CONSTRAINT package_category_fkey FOREIGN KEY (category) REFERENCES public.category(id) ON DELETE CASCADE;


--
-- Name: package_changelog_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_changelog
    ADD CONSTRAINT package_changelog_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_files_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_files
    ADD CONSTRAINT package_files_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_manifest_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_manifest
    ADD CONSTRAINT package_manifest_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_mask_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_mask
    ADD CONSTRAINT package_mask_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_recent_arch_arch_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_recent_arch
    ADD CONSTRAINT package_recent_arch_arch_fkey FOREIGN KEY (arch) REFERENCES public.arch(id) ON DELETE CASCADE;


--
-- Name: package_recent_arch_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_recent_arch
    ADD CONSTRAINT package_recent_arch_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_recent_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_recent
    ADD CONSTRAINT package_recent_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_use_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_use
    ADD CONSTRAINT package_use_package_fkey FOREIGN KEY (package) REFERENCES public.package(id) ON DELETE CASCADE;


--
-- Name: package_use_use_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_use
    ADD CONSTRAINT package_use_use_fkey FOREIGN KEY (use) REFERENCES public.use(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

