--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: -; Owner: steve
--

CREATE PROCEDURAL LANGUAGE plpgsql;


ALTER PROCEDURAL LANGUAGE plpgsql OWNER TO steve;

SET search_path = public, pg_catalog;

--
-- Name: merge_db(integer, text); Type: FUNCTION; Schema: public; Owner: steve
--

CREATE FUNCTION merge_db(key integer, data text) RETURNS void
    LANGUAGE plpgsql
    AS $$BEGIN
IF 1 =1 THEN
return 'foo';
end if;
END;
$$;


ALTER FUNCTION public.merge_db(key integer, data text) OWNER TO steve;

--
-- Name: plpgsql_call_handler(); Type: FUNCTION; Schema: public; Owner: steve
--

CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler
    LANGUAGE c
    AS '$libdir/plpgsql', 'plpgsql_call_handler';


ALTER FUNCTION public.plpgsql_call_handler() OWNER TO steve;

--
-- Name: plpgsql_validator(oid); Type: FUNCTION; Schema: public; Owner: steve
--

CREATE FUNCTION plpgsql_validator(oid) RETURNS void
    LANGUAGE c
    AS '$libdir/plpgsql', 'plpgsql_validator';


ALTER FUNCTION public.plpgsql_validator(oid) OWNER TO steve;

--
-- Name: truncate_ebuild_tables(); Type: FUNCTION; Schema: public; Owner: steve
--

CREATE FUNCTION truncate_ebuild_tables() RETURNS void
    LANGUAGE sql
    AS $$ TRUNCATE ebuild_arch; TRUNCATE ebuild_eclass; TRUNCATE ebuild_homepage; TRUNCATE ebuild_license; TRUNCATE ebuild_use; $$;


ALTER FUNCTION public.truncate_ebuild_tables() OWNER TO steve;

SET default_tablespace = '';

SET default_with_oids = true;

--
-- Name: arch; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE arch (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.arch OWNER TO steve;

--
-- Name: arch_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE arch_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.arch_id_seq OWNER TO steve;

--
-- Name: arch_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE arch_id_seq OWNED BY arch.id;


--
-- Name: category; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE category (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.category OWNER TO steve;

--
-- Name: category_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE category_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.category_id_seq OWNER TO steve;

--
-- Name: category_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE category_id_seq OWNED BY category.id;


SET default_with_oids = false;

--
-- Name: ebuild; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild (
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
    mtime bigint,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    ev character varying DEFAULT ''::character varying NOT NULL,
    lvl smallint DEFAULT 0 NOT NULL
);


ALTER TABLE public.ebuild OWNER TO steve;

--
-- Name: COLUMN ebuild.status; Type: COMMENT; Schema: public; Owner: steve
--

COMMENT ON COLUMN ebuild.status IS 'fine, deleted, updating';


SET default_with_oids = true;

--
-- Name: ebuild_arch; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild_arch (
    ebuild integer NOT NULL,
    arch integer NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    masked boolean DEFAULT false NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.ebuild_arch OWNER TO steve;

--
-- Name: COLUMN ebuild_arch.status; Type: COMMENT; Schema: public; Owner: steve
--

COMMENT ON COLUMN ebuild_arch.status IS 'stable, unstable, no workie';


--
-- Name: ebuild_eclass; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild_eclass (
    ebuild integer NOT NULL,
    eclass integer NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.ebuild_eclass OWNER TO steve;

--
-- Name: ebuild_homepage; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild_homepage (
    ebuild integer NOT NULL,
    homepage text DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.ebuild_homepage OWNER TO steve;

--
-- Name: ebuild_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE ebuild_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ebuild_id_seq OWNER TO steve;

--
-- Name: ebuild_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE ebuild_id_seq OWNED BY ebuild.id;


--
-- Name: ebuild_license; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild_license (
    ebuild integer NOT NULL,
    license smallint NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.ebuild_license OWNER TO steve;

SET default_with_oids = false;

--
-- Name: ebuild_mask; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild_mask (
    package_mask integer,
    ebuild integer
);


ALTER TABLE public.ebuild_mask OWNER TO steve;

--
-- Name: ebuild_metadata; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild_metadata (
    ebuild integer NOT NULL,
    keyword character varying(255) DEFAULT ''::character varying NOT NULL,
    value text DEFAULT ''::text NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.ebuild_metadata OWNER TO steve;

SET default_with_oids = true;

--
-- Name: package; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE package (
    id integer NOT NULL,
    category integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    changelog text DEFAULT ''::text NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL,
    mtime bigint,
    ctime bigint
);


ALTER TABLE public.package OWNER TO steve;

--
-- Name: ebuilds; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW ebuilds AS
    SELECT e.id, c.name AS category_name, c.id AS category, p.name AS package_name, e.package, e.pf, e.pv, e.pr, e.pvr, e.alpha, e.beta, e.pre, e.rc, e.p, e.slot, e.version, e.ev, e.lvl, e.mtime, e.idate FROM ((ebuild e JOIN package p ON ((e.package = p.id))) JOIN category c ON ((c.id = p.category))) WHERE (e.status = 0);


ALTER TABLE public.ebuilds OWNER TO steve;

--
-- Name: ebuild_order; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW ebuild_order AS
    SELECT ebuilds.category, ebuilds.category_name, ebuilds.package, ebuilds.package_name, ebuilds.id AS ebuild FROM ebuilds ORDER BY ebuilds.category_name, ebuilds.package_name, ebuilds.ev DESC, ebuilds.lvl DESC, (ebuilds.p IS NULL), ebuilds.p DESC, (ebuilds.rc IS NULL), ebuilds.rc DESC, (ebuilds.pre IS NULL), ebuilds.pre DESC, (ebuilds.beta IS NULL), ebuilds.beta DESC, (ebuilds.alpha IS NULL), ebuilds.alpha DESC, (ebuilds.pr IS NULL), ebuilds.pr DESC;


ALTER TABLE public.ebuild_order OWNER TO steve;

--
-- Name: ebuild_use; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild_use (
    ebuild integer NOT NULL,
    use integer NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.ebuild_use OWNER TO steve;

SET default_with_oids = false;

--
-- Name: ebuild_version; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE ebuild_version (
    ebuild integer NOT NULL,
    version character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.ebuild_version OWNER TO steve;

SET default_with_oids = true;

--
-- Name: eclass; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE eclass (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.eclass OWNER TO steve;

--
-- Name: eclass_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE eclass_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.eclass_id_seq OWNER TO steve;

--
-- Name: eclass_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE eclass_id_seq OWNED BY eclass.id;


--
-- Name: herd; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE herd (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    email character varying(255) DEFAULT ''::character varying NOT NULL,
    description text DEFAULT ''::text NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.herd OWNER TO steve;

--
-- Name: herd_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE herd_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.herd_id_seq OWNER TO steve;

--
-- Name: herd_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE herd_id_seq OWNED BY herd.id;


--
-- Name: license; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE license (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.license OWNER TO steve;

--
-- Name: license_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE license_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.license_id_seq OWNER TO steve;

--
-- Name: license_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE license_id_seq OWNED BY license.id;


SET default_with_oids = false;

--
-- Name: meta; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE meta (
    keyword character varying(255) DEFAULT ''::character varying NOT NULL,
    value character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.meta OWNER TO steve;

--
-- Name: missing_arch; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW missing_arch AS
    SELECT e.id, c.name AS category, p.name AS package, e.pf, em.value AS metadata FROM ((((category c JOIN package p ON ((p.category = c.id))) JOIN ebuild e ON ((e.package = p.id))) LEFT JOIN ebuild_arch ea ON ((ea.ebuild = e.id))) LEFT JOIN ebuild_metadata em ON (((em.ebuild = e.id) AND ((em.keyword)::text = 'keywords'::text)))) WHERE ((ea.ebuild IS NULL) AND (em.value <> ''::text));


ALTER TABLE public.missing_arch OWNER TO steve;

--
-- Name: missing_ev; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW missing_ev AS
    SELECT DISTINCT e1.package, e2.id AS ebuild, e2.version FROM (ebuild e1 LEFT JOIN ebuild e2 ON ((e2.package = e1.package))) WHERE ((e1.status = 2) OR ((e1.ev)::text = ''::text)) ORDER BY e1.package;


ALTER TABLE public.missing_ev OWNER TO steve;

--
-- Name: missing_homepage; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW missing_homepage AS
    SELECT e.id, c.name AS category, p.name AS package, e.pf, em.value AS metadata FROM ((((category c JOIN package p ON ((p.category = c.id))) JOIN ebuild e ON ((e.package = p.id))) LEFT JOIN ebuild_homepage eh ON ((eh.ebuild = e.id))) LEFT JOIN ebuild_metadata em ON (((em.ebuild = e.id) AND ((em.keyword)::text = 'homepage'::text)))) WHERE ((eh.ebuild IS NULL) AND (em.value <> ''::text));


ALTER TABLE public.missing_homepage OWNER TO steve;

--
-- Name: missing_license; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW missing_license AS
    SELECT e.id, c.name AS category, p.name AS package, e.pf, em.value AS metadata FROM ((((category c JOIN package p ON ((p.category = c.id))) JOIN ebuild e ON ((e.package = p.id))) LEFT JOIN ebuild_license el ON ((el.ebuild = e.id))) LEFT JOIN ebuild_metadata em ON (((em.ebuild = e.id) AND ((em.keyword)::text = 'license'::text)))) WHERE ((el.ebuild IS NULL) AND (em.value <> ''::text));


ALTER TABLE public.missing_license OWNER TO steve;

--
-- Name: missing_metadata; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW missing_metadata AS
    SELECT DISTINCT e.id, c.name AS category, p.name AS package, e.pf FROM (((category c JOIN package p ON ((p.category = c.id))) JOIN ebuild e ON ((e.package = p.id))) LEFT JOIN ebuild_metadata em ON ((em.ebuild = e.id))) WHERE (em.ebuild IS NULL);


ALTER TABLE public.missing_metadata OWNER TO steve;

SET default_with_oids = true;

--
-- Name: package_herd; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE package_herd (
    package integer NOT NULL,
    herd integer NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.package_herd OWNER TO steve;

--
-- Name: package_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE package_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.package_id_seq OWNER TO steve;

--
-- Name: package_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE package_id_seq OWNED BY package.id;


--
-- Name: package_maintainer; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE package_maintainer (
    package integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    email character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.package_maintainer OWNER TO steve;

--
-- Name: package_mask_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE package_mask_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.package_mask_id_seq OWNER TO steve;

SET default_with_oids = false;

--
-- Name: package_mask; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE package_mask (
    id integer DEFAULT nextval('package_mask_id_seq'::regclass) NOT NULL,
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
    version character varying(255) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.package_mask OWNER TO steve;

--
-- Name: view_ebuild; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW view_ebuild AS
    SELECT e.id, c.name AS category_name, c.id AS category, p.name AS package_name, e.package, e.pf, e.pv, e.pr, e.pvr, e.alpha, e.beta, e.pre, e.rc, e.p, e.slot, e.version, e.ev, e.lvl, e.mtime, e.idate FROM ((ebuild e JOIN package p ON ((e.package = p.id))) JOIN category c ON ((c.id = p.category)));


ALTER TABLE public.view_ebuild OWNER TO steve;

--
-- Name: search_ebuilds; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW search_ebuilds AS
    SELECT DISTINCT e.package, e.category_name, e.package_name, (((e.category_name)::text || '/'::text) || (e.pf)::text) AS atom, em.value AS description FROM (view_ebuild e JOIN ebuild_metadata em ON ((((em.keyword)::text = 'description'::text) AND (em.ebuild = e.id))));


ALTER TABLE public.search_ebuilds OWNER TO steve;

SET default_with_oids = true;

--
-- Name: use; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE use (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    description text DEFAULT ''::text NOT NULL,
    package integer,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.use OWNER TO steve;

--
-- Name: use_expand; Type: TABLE; Schema: public; Owner: steve; Tablespace: 
--

CREATE TABLE use_expand (
    id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    idate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.use_expand OWNER TO steve;

--
-- Name: use_expand_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE use_expand_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.use_expand_id_seq OWNER TO steve;

--
-- Name: use_expand_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE use_expand_id_seq OWNED BY use_expand.id;


--
-- Name: use_id_seq; Type: SEQUENCE; Schema: public; Owner: steve
--

CREATE SEQUENCE use_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.use_id_seq OWNER TO steve;

--
-- Name: use_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: steve
--

ALTER SEQUENCE use_id_seq OWNED BY use.id;


--
-- Name: view_arches; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW view_arches AS
    SELECT e.id AS ebuild, ea.arch, a.name, ea.status FROM ((ebuild e JOIN ebuild_arch ea ON ((ea.ebuild = e.id))) JOIN arch a ON ((ea.arch = a.id)));


ALTER TABLE public.view_arches OWNER TO steve;

--
-- Name: view_ebuild_level; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW view_ebuild_level AS
    SELECT e.id, CASE WHEN (e.p IS NOT NULL) THEN 6 WHEN (e.rc IS NOT NULL) THEN 4 WHEN (e.pre IS NOT NULL) THEN 3 WHEN (e.beta IS NOT NULL) THEN 2 WHEN (e.alpha IS NOT NULL) THEN 1 ELSE 5 END AS level FROM ebuild e;


ALTER TABLE public.view_ebuild_level OWNER TO steve;

--
-- Name: view_ebuild_order; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW view_ebuild_order AS
    SELECT view_ebuild.category, view_ebuild.category_name, view_ebuild.package, view_ebuild.package_name, view_ebuild.id AS ebuild FROM view_ebuild ORDER BY view_ebuild.category_name, view_ebuild.package_name, view_ebuild.ev DESC, view_ebuild.lvl DESC, (view_ebuild.p IS NULL), view_ebuild.p DESC, (view_ebuild.rc IS NULL), view_ebuild.rc DESC, (view_ebuild.pre IS NULL), view_ebuild.pre DESC, (view_ebuild.beta IS NULL), view_ebuild.beta DESC, (view_ebuild.alpha IS NULL), view_ebuild.alpha DESC, (view_ebuild.pr IS NULL), view_ebuild.pr DESC;


ALTER TABLE public.view_ebuild_order OWNER TO steve;

--
-- Name: view_licenses; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW view_licenses AS
    SELECT e.id AS ebuild, el.license, l.name FROM ((ebuild e JOIN ebuild_license el ON ((el.ebuild = e.id))) JOIN license l ON ((el.license = l.id)));


ALTER TABLE public.view_licenses OWNER TO steve;

--
-- Name: view_pmask_level; Type: VIEW; Schema: public; Owner: steve
--

CREATE VIEW view_pmask_level AS
    SELECT pm.id, CASE WHEN (pm.p IS NOT NULL) THEN 6 WHEN (pm.rc IS NOT NULL) THEN 4 WHEN (pm.pre IS NOT NULL) THEN 3 WHEN (pm.beta IS NOT NULL) THEN 2 WHEN (pm.alpha IS NOT NULL) THEN 1 ELSE 5 END AS level FROM package_mask pm;


ALTER TABLE public.view_pmask_level OWNER TO steve;

--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE arch ALTER COLUMN id SET DEFAULT nextval('arch_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE category ALTER COLUMN id SET DEFAULT nextval('category_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE ebuild ALTER COLUMN id SET DEFAULT nextval('ebuild_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE eclass ALTER COLUMN id SET DEFAULT nextval('eclass_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE herd ALTER COLUMN id SET DEFAULT nextval('herd_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE license ALTER COLUMN id SET DEFAULT nextval('license_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE package ALTER COLUMN id SET DEFAULT nextval('package_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE use ALTER COLUMN id SET DEFAULT nextval('use_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: steve
--

ALTER TABLE use_expand ALTER COLUMN id SET DEFAULT nextval('use_expand_id_seq'::regclass);


--
-- Name: category_pkey; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY category
    ADD CONSTRAINT category_pkey PRIMARY KEY (id);


--
-- Name: ebuild_metadata_ebuild_key; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY ebuild_metadata
    ADD CONSTRAINT ebuild_metadata_ebuild_key UNIQUE (ebuild, keyword);


--
-- Name: ebuild_package_key; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY ebuild
    ADD CONSTRAINT ebuild_package_key UNIQUE (package, pf, mtime);


--
-- Name: ebuild_pkey; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY ebuild
    ADD CONSTRAINT ebuild_pkey PRIMARY KEY (id);


--
-- Name: package_mask_pkey; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY package_mask
    ADD CONSTRAINT package_mask_pkey PRIMARY KEY (id);


--
-- Name: package_pkey; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY package
    ADD CONSTRAINT package_pkey PRIMARY KEY (id);


--
-- Name: pkey_arch; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY arch
    ADD CONSTRAINT pkey_arch PRIMARY KEY (id);


--
-- Name: pkey_ebuild_use; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY ebuild_use
    ADD CONSTRAINT pkey_ebuild_use PRIMARY KEY (ebuild, use);


--
-- Name: pkey_eclass; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY eclass
    ADD CONSTRAINT pkey_eclass PRIMARY KEY (id);


--
-- Name: pkey_herd; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY herd
    ADD CONSTRAINT pkey_herd PRIMARY KEY (id);


--
-- Name: pkey_license; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY license
    ADD CONSTRAINT pkey_license PRIMARY KEY (id);


--
-- Name: uniq_arch; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY arch
    ADD CONSTRAINT uniq_arch UNIQUE (name);


--
-- Name: uniq_category_name; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY category
    ADD CONSTRAINT uniq_category_name UNIQUE (name);


--
-- Name: uniq_ebuild_arch; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY ebuild_arch
    ADD CONSTRAINT uniq_ebuild_arch UNIQUE (ebuild, arch);


--
-- Name: uniq_ebuild_eclass; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY ebuild_eclass
    ADD CONSTRAINT uniq_ebuild_eclass UNIQUE (ebuild, eclass);


--
-- Name: uniq_ebuild_homepage; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY ebuild_homepage
    ADD CONSTRAINT uniq_ebuild_homepage UNIQUE (ebuild, homepage);


--
-- Name: uniq_ebuild_license; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY ebuild_license
    ADD CONSTRAINT uniq_ebuild_license UNIQUE (ebuild, license);


--
-- Name: uniq_eclass_name; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY eclass
    ADD CONSTRAINT uniq_eclass_name UNIQUE (name);


--
-- Name: uniq_herd_name; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY herd
    ADD CONSTRAINT uniq_herd_name UNIQUE (name);


--
-- Name: uniq_license_name; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY license
    ADD CONSTRAINT uniq_license_name UNIQUE (name);


--
-- Name: uniq_package_herd; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY package_herd
    ADD CONSTRAINT uniq_package_herd UNIQUE (package, herd);


--
-- Name: uniq_use_expand_name; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY use_expand
    ADD CONSTRAINT uniq_use_expand_name UNIQUE (name);


--
-- Name: uniq_use_name_description_package; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY use
    ADD CONSTRAINT uniq_use_name_description_package UNIQUE (name, description, package);


--
-- Name: use_expand_pkey; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY use_expand
    ADD CONSTRAINT use_expand_pkey PRIMARY KEY (id);


--
-- Name: use_pkey; Type: CONSTRAINT; Schema: public; Owner: steve; Tablespace: 
--

ALTER TABLE ONLY use
    ADD CONSTRAINT use_pkey PRIMARY KEY (id);


--
-- Name: uniq_cat_name; Type: INDEX; Schema: public; Owner: steve; Tablespace: 
--

CREATE UNIQUE INDEX uniq_cat_name ON category USING btree (name);


--
-- Name: uniq_package_name; Type: INDEX; Schema: public; Owner: steve; Tablespace: 
--

CREATE UNIQUE INDEX uniq_package_name ON package USING btree (category, name);

ALTER TABLE package CLUSTER ON uniq_package_name;


--
-- Name: ebuild_arch_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_arch
    ADD CONSTRAINT ebuild_arch_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_eclass_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_eclass
    ADD CONSTRAINT ebuild_eclass_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_homepage_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_homepage
    ADD CONSTRAINT ebuild_homepage_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_license_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_license
    ADD CONSTRAINT ebuild_license_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_metadata_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_metadata
    ADD CONSTRAINT ebuild_metadata_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild
    ADD CONSTRAINT ebuild_package_fkey FOREIGN KEY (package) REFERENCES package(id) ON DELETE CASCADE;


--
-- Name: ebuild_use_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_use
    ADD CONSTRAINT ebuild_use_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES ebuild(id) ON DELETE CASCADE;


--
-- Name: ebuild_version_ebuild_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_version
    ADD CONSTRAINT ebuild_version_ebuild_fkey FOREIGN KEY (ebuild) REFERENCES ebuild(id) ON DELETE CASCADE;


--
-- Name: fkey_ebuild_arch_arch; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_arch
    ADD CONSTRAINT fkey_ebuild_arch_arch FOREIGN KEY (arch) REFERENCES arch(id) ON DELETE CASCADE;


--
-- Name: fkey_ebuild_eclass_eclass; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_eclass
    ADD CONSTRAINT fkey_ebuild_eclass_eclass FOREIGN KEY (eclass) REFERENCES eclass(id) ON DELETE CASCADE;


--
-- Name: fkey_ebuild_license_license; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_license
    ADD CONSTRAINT fkey_ebuild_license_license FOREIGN KEY (license) REFERENCES license(id) ON DELETE CASCADE;


--
-- Name: fkey_ebuild_use_use; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY ebuild_use
    ADD CONSTRAINT fkey_ebuild_use_use FOREIGN KEY (use) REFERENCES use(id) ON DELETE CASCADE;


--
-- Name: fkey_herd_package; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY package_herd
    ADD CONSTRAINT fkey_herd_package FOREIGN KEY (herd) REFERENCES herd(id) ON DELETE CASCADE;


--
-- Name: fkey_package_herd_package; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY package_herd
    ADD CONSTRAINT fkey_package_herd_package FOREIGN KEY (package) REFERENCES package(id) ON DELETE CASCADE;


--
-- Name: fkey_package_maintainer_package; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY package_maintainer
    ADD CONSTRAINT fkey_package_maintainer_package FOREIGN KEY (package) REFERENCES package(id) ON DELETE CASCADE;


--
-- Name: package_category_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY package
    ADD CONSTRAINT package_category_fkey FOREIGN KEY (category) REFERENCES category(id) ON DELETE CASCADE;


--
-- Name: package_mask_package_fkey; Type: FK CONSTRAINT; Schema: public; Owner: steve
--

ALTER TABLE ONLY package_mask
    ADD CONSTRAINT package_mask_package_fkey FOREIGN KEY (package) REFERENCES package(id) ON DELETE CASCADE;


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

