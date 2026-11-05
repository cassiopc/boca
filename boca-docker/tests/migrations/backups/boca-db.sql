--
-- PostgreSQL database dump
--

-- Dumped from database version 14.8
-- Dumped by pg_dump version 14.8

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

DROP DATABASE bocadb;
--
-- Name: bocadb; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE bocadb WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE = 'en_US.utf8';


ALTER DATABASE bocadb OWNER TO postgres;

\connect bocadb

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: answertable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.answertable (
    contestnumber integer NOT NULL,
    answernumber integer NOT NULL,
    runanswer character varying(50) NOT NULL,
    yes boolean DEFAULT false NOT NULL,
    fake boolean DEFAULT false NOT NULL,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.answertable OWNER TO bocauser;

--
-- Name: bkptable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.bkptable (
    contestnumber integer NOT NULL,
    sitenumber integer NOT NULL,
    bkpnumber integer NOT NULL,
    usernumber integer NOT NULL,
    bkpdate integer NOT NULL,
    bkpfilename character varying(200) NOT NULL,
    bkpdata oid NOT NULL,
    bkpstatus character varying(50) NOT NULL,
    bkpsize integer NOT NULL,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.bkptable OWNER TO bocauser;

--
-- Name: clartable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.clartable (
    contestnumber integer NOT NULL,
    clarsitenumber integer NOT NULL,
    clarnumber integer NOT NULL,
    usernumber integer NOT NULL,
    clardate integer NOT NULL,
    clardatediff integer NOT NULL,
    clardatediffans integer NOT NULL,
    clarproblem integer NOT NULL,
    clardata text NOT NULL,
    claranswer text,
    clarstatus character varying(20) NOT NULL,
    clarjudge integer,
    clarjudgesite integer,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.clartable OWNER TO bocauser;

--
-- Name: contesttable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.contesttable (
    contestnumber integer NOT NULL,
    contestname character varying(100) NOT NULL,
    conteststartdate integer NOT NULL,
    contestduration integer NOT NULL,
    contestlastmileanswer integer,
    contestlastmilescore integer,
    contestlocalsite integer NOT NULL,
    contestpenalty integer NOT NULL,
    contestmaxfilesize integer NOT NULL,
    contestactive boolean NOT NULL,
    contestmainsite integer NOT NULL,
    contestkeys text NOT NULL,
    contestunlockkey character varying(100) NOT NULL,
    contestmainsiteurl character varying(200) NOT NULL,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.contesttable OWNER TO bocauser;

--
-- Name: langtable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.langtable (
    contestnumber integer NOT NULL,
    langnumber integer NOT NULL,
    langname character varying(50) NOT NULL,
    langextension character varying(20) NOT NULL,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.langtable OWNER TO bocauser;

--
-- Name: logtable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.logtable (
    lognumber integer NOT NULL,
    contestnumber integer NOT NULL,
    sitenumber integer NOT NULL,
    loguser integer,
    logip character varying(20) NOT NULL,
    logdate integer NOT NULL,
    logtype character varying(20) NOT NULL,
    logdata text NOT NULL,
    logstatus character varying(20) DEFAULT ''::character varying
);


ALTER TABLE public.logtable OWNER TO bocauser;

--
-- Name: logtable_lognumber_seq; Type: SEQUENCE; Schema: public; Owner: bocauser
--

CREATE SEQUENCE public.logtable_lognumber_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.logtable_lognumber_seq OWNER TO bocauser;

--
-- Name: logtable_lognumber_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: bocauser
--

ALTER SEQUENCE public.logtable_lognumber_seq OWNED BY public.logtable.lognumber;


--
-- Name: problemtable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.problemtable (
    contestnumber integer NOT NULL,
    problemnumber integer NOT NULL,
    problemname character varying(20) NOT NULL,
    problemfullname character varying(100) DEFAULT ''::character varying,
    problembasefilename character varying(100),
    probleminputfilename character varying(100) DEFAULT ''::character varying,
    probleminputfile oid,
    probleminputfilehash character varying(50),
    fake boolean DEFAULT false NOT NULL,
    problemcolorname character varying(100) DEFAULT ''::character varying,
    problemcolor character varying(6) DEFAULT ''::character varying,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.problemtable OWNER TO bocauser;

--
-- Name: runtable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.runtable (
    contestnumber integer NOT NULL,
    runsitenumber integer NOT NULL,
    runnumber integer NOT NULL,
    usernumber integer NOT NULL,
    rundate integer NOT NULL,
    rundatediff integer NOT NULL,
    rundatediffans integer NOT NULL,
    runproblem integer NOT NULL,
    runfilename character varying(200) NOT NULL,
    rundata oid NOT NULL,
    runanswer integer DEFAULT 0 NOT NULL,
    runstatus character varying(20) NOT NULL,
    runjudge integer,
    runjudgesite integer,
    runanswer1 integer DEFAULT 0 NOT NULL,
    runjudge1 integer,
    runjudgesite1 integer,
    runanswer2 integer DEFAULT 0 NOT NULL,
    runjudge2 integer,
    runjudgesite2 integer,
    runlangnumber integer NOT NULL,
    autoip character varying(20) DEFAULT ''::character varying,
    autobegindate integer,
    autoenddate integer,
    autoanswer text DEFAULT ''::text,
    autostdout oid,
    autostderr oid,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.runtable OWNER TO bocauser;

--
-- Name: sitetable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.sitetable (
    contestnumber integer NOT NULL,
    sitenumber integer NOT NULL,
    siteip character varying(200) NOT NULL,
    sitename character varying(50) NOT NULL,
    siteactive boolean NOT NULL,
    sitepermitlogins boolean NOT NULL,
    sitelastmileanswer integer,
    sitelastmilescore integer,
    siteduration integer,
    siteautoend boolean,
    sitejudging text,
    sitetasking text,
    siteglobalscore character varying(50) DEFAULT ''::character varying NOT NULL,
    sitescorelevel integer DEFAULT 0 NOT NULL,
    sitenextuser integer DEFAULT 0 NOT NULL,
    sitenextclar integer DEFAULT 0 NOT NULL,
    sitenextrun integer DEFAULT 0 NOT NULL,
    sitenexttask integer DEFAULT 0 NOT NULL,
    sitemaxtask integer DEFAULT 8 NOT NULL,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL,
    sitechiefname character varying(20) DEFAULT ''::character varying NOT NULL,
    siteautojudge boolean DEFAULT false,
    sitemaxruntime integer DEFAULT 600 NOT NULL,
    sitemaxjudgewaittime integer DEFAULT 900 NOT NULL
);


ALTER TABLE public.sitetable OWNER TO bocauser;

--
-- Name: sitetimetable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.sitetimetable (
    contestnumber integer NOT NULL,
    sitenumber integer NOT NULL,
    sitestartdate integer NOT NULL,
    siteenddate integer NOT NULL,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.sitetimetable OWNER TO bocauser;

--
-- Name: tasktable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.tasktable (
    contestnumber integer NOT NULL,
    sitenumber integer NOT NULL,
    usernumber integer NOT NULL,
    tasknumber integer NOT NULL,
    taskstaffnumber integer,
    taskstaffsite integer,
    taskdate integer NOT NULL,
    taskdatediff integer NOT NULL,
    taskdatediffans integer NOT NULL,
    taskdesc character varying(200),
    taskfilename character varying(100),
    taskdata oid,
    tasksystem boolean NOT NULL,
    taskstatus character varying(20) NOT NULL,
    colorname character varying(100) DEFAULT ''::character varying,
    color character varying(6) DEFAULT ''::character varying,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL
);


ALTER TABLE public.tasktable OWNER TO bocauser;

--
-- Name: usertable; Type: TABLE; Schema: public; Owner: bocauser
--

CREATE TABLE public.usertable (
    contestnumber integer NOT NULL,
    usersitenumber integer NOT NULL,
    usernumber integer NOT NULL,
    username character varying(20) NOT NULL,
    userfullname character varying(200) NOT NULL,
    userdesc character varying(300),
    usertype character varying(20) NOT NULL,
    userenabled boolean DEFAULT true NOT NULL,
    usermultilogin boolean DEFAULT false NOT NULL,
    userpassword character varying(200) DEFAULT ''::character varying,
    userip character varying(300),
    userlastlogin integer,
    usersession character varying(50) DEFAULT ''::character varying,
    usersessionextra character varying(50) DEFAULT ''::character varying,
    userlastlogout integer,
    userpermitip character varying(300),
    userinfo character varying(300) DEFAULT ''::character varying,
    updatetime integer DEFAULT EXTRACT(epoch FROM now()) NOT NULL,
    usericpcid character varying(50) DEFAULT ''::character varying
);


ALTER TABLE public.usertable OWNER TO bocauser;

--
-- Name: logtable lognumber; Type: DEFAULT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.logtable ALTER COLUMN lognumber SET DEFAULT nextval('public.logtable_lognumber_seq'::regclass);


--
-- Name: 16604; Type: BLOB; Schema: -; Owner: bocauser
--

SELECT pg_catalog.lo_create('16604');


ALTER LARGE OBJECT 16604 OWNER TO bocauser;

--
-- Name: 16605; Type: BLOB; Schema: -; Owner: bocauser
--

SELECT pg_catalog.lo_create('16605');


ALTER LARGE OBJECT 16605 OWNER TO bocauser;

--
-- Data for Name: answertable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.answertable (contestnumber, answernumber, runanswer, yes, fake, updatetime) FROM stdin;
1	0	Not answered yet	f	t	1687245645
1	1	YES	t	f	1687245645
1	2	NO - Compilation error	f	f	1687245645
1	3	NO - Runtime error	f	f	1687245645
1	4	NO - Time limit exceeded	f	f	1687245645
1	5	NO - Presentation error	f	f	1687245645
1	6	NO - Wrong answer	f	f	1687245645
1	7	NO - Contact staff	f	f	1687245645
1	8	NO - Name mismatch	f	f	1687245645
\.


--
-- Data for Name: bkptable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.bkptable (contestnumber, sitenumber, bkpnumber, usernumber, bkpdate, bkpfilename, bkpdata, bkpstatus, bkpsize, updatetime) FROM stdin;
\.


--
-- Data for Name: clartable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.clartable (contestnumber, clarsitenumber, clarnumber, usernumber, clardate, clardatediff, clardatediffans, clarproblem, clardata, claranswer, clarstatus, clarjudge, clarjudgesite, updatetime) FROM stdin;
\.


--
-- Data for Name: contesttable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.contesttable (contestnumber, contestname, conteststartdate, contestduration, contestlastmileanswer, contestlastmilescore, contestlocalsite, contestpenalty, contestmaxfilesize, contestactive, contestmainsite, contestkeys, contestunlockkey, contestmainsiteurl, updatetime) FROM stdin;
0	Fake contest (just for initial purposes)	1687245635	0	0	0	1	1200	100000	f	1				1687245635
1	Contest	1687264200	18000	17100	14400	1	1200	100000	t	1				1687245655
\.


--
-- Data for Name: langtable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.langtable (contestnumber, langnumber, langname, langextension, updatetime) FROM stdin;
1	1	C	c	1687245645
1	2	C++11	cc	1687245645
1	3	Java	java	1687245645
1	4	Python2	py2	1687245645
1	5	Python3	py3	1687245645
\.


--
-- Data for Name: logtable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.logtable (lognumber, contestnumber, sitenumber, loguser, logip, logdate, logtype, logdata, logstatus) FROM stdin;
1	0	1	1	172.20.0.1	1687245640	info	User system authenticated (172.20.0.1)	
2	0	1	1	172.20.0.1	1687245644	info	User system/1 created site 1 on contest 1.	
3	0	1	1	172.20.0.1	1687245644	info	Language 1 updated (user=1,site=1,contest=1)	
4	0	1	1	172.20.0.1	1687245644	info	Language 2 updated (user=1,site=1,contest=1)	
5	0	1	1	172.20.0.1	1687245644	info	Language 3 updated (user=1,site=1,contest=1)	
6	0	1	1	172.20.0.1	1687245644	info	Language 4 updated (user=1,site=1,contest=1)	
7	0	1	1	172.20.0.1	1687245644	info	Language 5 updated (user=1,site=1,contest=1)	
8	0	1	1	172.20.0.1	1687245644	info	User system/1 created a new contest (1).	
9	0	1	1	172.20.0.1	1687245653	info	User system/1 changed the contest 1 settings.	
10	0	1	1	172.20.0.1	1687245655	info	User system/1 activated contest 1.	
11	0	1	1	172.20.0.1	1687245655	info	User system/1 changed the contest 1 settings.	
12	0	1	1	172.20.0.1	1687245656	info	User 1 (contest=0,site=1) logged out.	
13	1	1	1000	172.20.0.1	1687245660	info	User admin authenticated (172.20.0.1)	
14	1	1	1000	172.20.0.1	1687245709	info	Problem 101 (inputfile=abacaxi.zip) created (user=1000,site=1,contest=1)	
15	1	1	1000	172.20.0.1	1687245730	info	Problem 102 (inputfile=bits.zip) created (user=1000,site=1,contest=1)	
\.


--
-- Data for Name: problemtable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.problemtable (contestnumber, problemnumber, problemname, problemfullname, problembasefilename, probleminputfilename, probleminputfile, probleminputfilehash, fake, problemcolorname, problemcolor, updatetime) FROM stdin;
1	0	General	General	\N	\N	\N	\N	t			1687245645
1	101	abacaxi	The abacaxi problem	abacaxi	abacaxi.zip	16604	b479fc22d0a383295eca4b2181d093812d2fc0ef	f	green	00FF00	1687245709
1	102	bits	The Problem of bits	bits	bits.zip	16605	3856c0ac0999b1ed81cbb9d6bf7f138bc442276a	f	yellow	FFFF00	1687245730
\.


--
-- Data for Name: runtable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.runtable (contestnumber, runsitenumber, runnumber, usernumber, rundate, rundatediff, rundatediffans, runproblem, runfilename, rundata, runanswer, runstatus, runjudge, runjudgesite, runanswer1, runjudge1, runjudgesite1, runanswer2, runjudge2, runjudgesite2, runlangnumber, autoip, autobegindate, autoenddate, autoanswer, autostdout, autostderr, updatetime) FROM stdin;
\.


--
-- Data for Name: sitetable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.sitetable (contestnumber, sitenumber, siteip, sitename, siteactive, sitepermitlogins, sitelastmileanswer, sitelastmilescore, siteduration, siteautoend, sitejudging, sitetasking, siteglobalscore, sitescorelevel, sitenextuser, sitenextclar, sitenextrun, sitenexttask, sitemaxtask, updatetime, sitechiefname, siteautojudge, sitemaxruntime, sitemaxjudgewaittime) FROM stdin;
0	1		Fake Site (just for initial purposes)	t	t	0	0	1	t	1	1	0	4	0	0	0	0	8	1687245635		f	600	900
1	1	127.0.0.1/boca	Site	t	t	17100	14400	18000	t	1	1	1	3	0	0	0	0	10	1687245644		f	600	900
\.


--
-- Data for Name: sitetimetable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.sitetimetable (contestnumber, sitenumber, sitestartdate, siteenddate, updatetime) FROM stdin;
0	1	1	0	1687245634
1	1	1687246245	0	1687245644
\.


--
-- Data for Name: tasktable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.tasktable (contestnumber, sitenumber, usernumber, tasknumber, taskstaffnumber, taskstaffsite, taskdate, taskdatediff, taskdatediffans, taskdesc, taskfilename, taskdata, tasksystem, taskstatus, colorname, color, updatetime) FROM stdin;
\.


--
-- Data for Name: usertable; Type: TABLE DATA; Schema: public; Owner: bocauser
--

COPY public.usertable (contestnumber, usersitenumber, usernumber, username, userfullname, userdesc, usertype, userenabled, usermultilogin, userpassword, userip, userlastlogin, usersession, usersessionextra, userlastlogout, userpermitip, userinfo, updatetime, usericpcid) FROM stdin;
0	1	1	system	Systems	\N	system	t	t	7ab2d6e73d6ed4fb40fc1f97f051a183d01c3f469255b841a1cb529699310d28	172.20.0.1	1687245640			1687245656	\N		1687245656	
1	1	1000	admin	Administrator	\N	admin	t	t	7ab2d6e73d6ed4fb40fc1f97f051a183d01c3f469255b841a1cb529699310d28	172.20.0.1	1687245660	mjhp7n3vg7uam540j1lthdd51p	mjhp7n3vg7uam540j1lthdd51p	\N	\N		1687245660	
\.


--
-- Name: logtable_lognumber_seq; Type: SEQUENCE SET; Schema: public; Owner: bocauser
--

SELECT pg_catalog.setval('public.logtable_lognumber_seq', 15, true);


--
-- Data for Name: BLOBS; Type: BLOBS; Schema: -; Owner: -
--

BEGIN;

SELECT pg_catalog.lo_open('16604', 131072);
SELECT pg_catalog.lowrite(0, '\x504b03040a0000000000433ee24200000000000000000000000008001c00636f6d706172652f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242a9caa15889040000cb0e000009001c00636f6d706172652f6355540900030d6ad2511aa8335275780b000104f50100000414000000b5575153db38107ecfaf580c0f3093c410eeee811ebd065adacc70c084301de67ad32ab61c8bca92cf9231f9f7f7494e9ccc11d2d003bfd892bc9ff6fb76575e6f6f8563a1c23133696b9bc217be3ce4c9e5699f2e95148ad3a956961b4bfd38134a185b30ab0bff16e13ad5f9b41093d4d2eee91ef5f6f70f3bbdfd831e8da7e441def37b2e759e716569c45946bb631d3133359667ef261913b21be96ccfe335a0a35418ca0b3d296080c7a4e09c8c4e6cc50a7e44535d52c414153c76fe88716939094b4cc5a12e28d3b148a60d18164a15f3826ccac9f22233a4133ff87871431fb9e2059374558ea588e85c445c194e0cfbbb1993f2185c1a306776e6bcb99e7943671ae8cc0aaddac405d60bbae785c1980ee71bcd50dbb424dc2eb38e48413a77c67bf07e4a92d9857d77bd280bee3109e5f749750e8a299041ba1252d29853697852ca7683030bfa3c187dbabc1951ffe2963ef787c3fec5e8f60d2c6caab18a90d57822cba5003c88164cd929f834307f7e189e7e825dff64703e18dd821a9d0d46171faeafe9ec72487dbaea0f4783d39bf3fe90ae6e865797d71fba44d7dc39c81b943521487c28a170cc2dd2c4741ba35bc4dfc0531953caee39f220e2e21e7e328a908e3f0e6f83c4a45613cf1b160b79e1e82021a56d9b0c1cfe3db5363f0ac3aaaaba135576753109658d64c2b7ddd72a423a67283b9fcd2e08bd83f0ae94e1bcba504448927722e3ddd2e4dd31320b563e494c5488dcce65314798df3940eab3ec2bc29b97d64df4505072697ce8c88f25cfbe0a8529dacd7c55d709e46a00ef67a8c29c47f027a228e5d177246a9b90f2befcb03324dbf37ef8da2f388b8d0f85744cfc69328b8db1285656c4d4388031e5e0e490aca6bb329e70032c91d05fb4459d82829d83803a7a3ee805f4f71b07a65a443c4a3505570cc1e3ae84665c02b7f400c45f0e5b8968619b0bfe507b323b051672dd95f01167518eaa36cb6a79d71662b94a62d255ca249dd1ce356281fd9ce351cad4c42779e6863e831daae40f282c264b7f56209b5b886b429d7f6a5e9ecfdb30e6f7a12a51b8bdc5732d41b0f34740c7c714ec3fe2dde11478b02f80fa12b85b0fb76d4a8404958cd9280d1a91ce1693d08645564e17323995e67e75c62fe85a2772789b38b86cb2fafd185fa419818e2fd03a300e616ed838be92b62f78f72a325745989e9d9f2cc3616e5d6254a9b02ed919561b757efd8f3a9d9317170890cfd5e8495937906939c42fa094af94b164ea7b5d624f28275e453cf1f3fa3d69b59984cb5456aa081e158ec889f2870173fd05847a6959bd239dea357212a8cf96757d3c3651f6519eac1117dd50e4be270c5c37285ef15a7289ffa1d87ac38d737199d3b3d2711305abf8f81b56ddd1e076fd3657a78a03da823ccbea542b3e4a4f684cb446e525ad56413e52f6a7e2f294bce40157e12c8b3c97f9044df563a9675d0555baf09d94bbb7eb0008f49fe8b78b295a11f728dd6f4269dcb3e7ba0cee9d48f16bc2d0c44f7ca3df763bb0bab58ac08009e5969dbd9c05b05d77397577368f72ddd175aa197c1d5e82142ec68d7adbdb3f4cbef5c2d47ebf5f3ae812f7bb8405b7e36fad7f01504b0304140000000800433ee242a9caa15889040000cb0e00000b001c00636f6d706172652f63707055540900030d6ad2511aa8335275780b000104f50100000414000000b5575153db38107ecfaf580c0f3093c410eeee811ebd065adacc70c084301de67ad32ab61c8bca92cf9231f9f7f7494e9ccc11d2d003bfd892bc9ff6fb76575e6f6f8563a1c23133696b9bc217be3ce4c9e5699f2e95148ad3a956961b4bfd38134a185b30ab0bff16e13ad5f9b41093d4d2eee91ef5f6f70f3bbdfd831e8da7e441def37b2e759e716569c45946bb631d3133359667ef261913b21be96ccfe335a0a35418ca0b3d296080c7a4e09c8c4e6cc50a7e44535d52c414153c76fe88716939094b4cc5a12e28d3b148a60d18164a15f3826ccac9f22233a4133ff87871431fb9e2059374558ea588e85c445c194e0cfbbb1993f2185c1a306776e6bcb99e7943671ae8cc0aaddac405d60bbae785c1980ee71bcd50dbb424dc2eb38e48413a77c67bf07e4a92d9857d77bd280bee3109e5f749750e8a299041ba1252d29853697852ca7683030bfa3c187dbabc1951ffe2963ef787c3fec5e8f60d2c6caab18a90d57822cba5003c88164cd929f834307f7e189e7e825dff64703e18dd821a9d0d46171faeafe9ec72487dbaea0f4783d39bf3fe90ae6e865797d71fba44d7dc39c81b943521487c28a170cc2dd2c4741ba35bc4dfc0531953caee39f220e2e21e7e328a908e3f0e6f83c4a45613cf1b160b79e1e82021a56d9b0c1cfe3db5363f0ac3aaaaba135576753109658d64c2b7ddd72a423a67283b9fcd2e08bd83f0ae94e1bcba504448927722e3ddd2e4dd31320b563e494c5488dcce65314798df3940eab3ec2bc29b97d64df4505072697ce8c88f25cfbe0a8529dacd7c55d709e46a00ef67a8c29c47f027a228e5d177246a9b90f2befcb03324dbf37ef8da2f388b8d0f85744cfc69328b8db1285656c4d4388031e5e0e490aca6bb329e70032c91d05fb4459d82829d83803a7a3ee805f4f71b07a65a443c4a3505570cc1e3ae84665c02b7f400c45f0e5b8968619b0bfe507b323b051672dd95f01167518eaa36cb6a79d71662b94a62d255ca249dd1ce356281fd9ce351cad4c42779e6863e831daae40f282c264b7f56209b5b886b429d7f6a5e9ecfdb30e6f7a12a51b8bdc5732d41b0f34740c7c714ec3fe2dde11478b02f80fa12b85b0fb76d4a8404958cd9280d1a91ce1693d08645564e17323995e67e75c62fe85a2772789b38b86cb2fafd185fa419818e2fd03a300e616ed838be92b62f78f72a325745989e9d9f2cc3616e5d6254a9b02ed919561b757efd8f3a9d9317170890cfd5e8495937906939c42fa094af94b164ea7b5d624f28275e453cf1f3fa3d69b59984cb5456aa081e158ec889f2870173fd05847a6959bd239dea357212a8cf96757d3c3651f6519eac1117dd50e4be270c5c37285ef15a7289ffa1d87ac38d737199d3b3d2711305abf8f81b56ddd1e076fd3657a78a03da823ccbea542b3e4a4f684cb446e525ad56413e52f6a7e2f294bce40157e12c8b3c97f9044df563a9675d0555baf09d94bbb7eb0008f49fe8b78b295a11f728dd6f4269dcb3e7ba0cee9d48f16bc2d0c44f7ca3df763bb0bab58ac08009e5969dbd9c05b05d77397577368f72ddd175aa197c1d5e82142ec68d7adbdb3f4cbef5c2d47ebf5f3ae812f7bb8405b7e36fad7f01504b0304140000000800433ee242a9caa15889040000cb0e00000c001c00636f6d706172652f6a61766155540900030d6ad2511aa8335275780b000104f50100000414000000b5575153db38107ecfaf580c0f3093c410eeee811ebd065adacc70c084301de67ad32ab61c8bca92cf9231f9f7f7494e9ccc11d2d003bfd892bc9ff6fb76575e6f6f8563a1c23133696b9bc217be3ce4c9e5699f2e95148ad3a956961b4bfd38134a185b30ab0bff16e13ad5f9b41093d4d2eee91ef5f6f70f3bbdfd831e8da7e441def37b2e759e716569c45946bb631d3133359667ef261913b21be96ccfe335a0a35418ca0b3d296080c7a4e09c8c4e6cc50a7e44535d52c414153c76fe88716939094b4cc5a12e28d3b148a60d18164a15f3826ccac9f22233a4133ff87871431fb9e2059374558ea588e85c445c194e0cfbbb1993f2185c1a306776e6bcb99e7943671ae8cc0aaddac405d60bbae785c1980ee71bcd50dbb424dc2eb38e48413a77c67bf07e4a92d9857d77bd280bee3109e5f749750e8a299041ba1252d29853697852ca7683030bfa3c187dbabc1951ffe2963ef787c3fec5e8f60d2c6caab18a90d57822cba5003c88164cd929f834307f7e189e7e825dff64703e18dd821a9d0d46171faeafe9ec72487dbaea0f4783d39bf3fe90ae6e865797d71fba44d7dc39c81b943521487c28a170cc2dd2c4741ba35bc4dfc0531953caee39f220e2e21e7e328a908e3f0e6f83c4a45613cf1b160b79e1e82021a56d9b0c1cfe3db5363f0ac3aaaaba135576753109658d64c2b7ddd72a423a67283b9fcd2e08bd83f0ae94e1bcba504448927722e3ddd2e4dd31320b563e494c5488dcce65314798df3940eab3ec2bc29b97d64df4505072697ce8c88f25cfbe0a8529dacd7c55d709e46a00ef67a8c29c47f027a228e5d177246a9b90f2befcb03324dbf37ef8da2f388b8d0f85744cfc69328b8db1285656c4d4388031e5e0e490aca6bb329e70032c91d05fb4459d82829d83803a7a3ee805f4f71b07a65a443c4a3505570cc1e3ae84665c02b7f400c45f0e5b8968619b0bfe507b323b051672dd95f01167518eaa36cb6a79d71662b94a62d255ca249dd1ce356281fd9ce351cad4c42779e6863e831daae40f282c264b7f56209b5b886b429d7f6a5e9ecfdb30e6f7a12a51b8bdc5732d41b0f34740c7c714ec3fe2dde11478b02f80fa12b85b0fb76d4a8404958cd9280d1a91ce1693d08645564e17323995e67e75c62fe85a2772789b38b86cb2fafd185fa419818e2fd03a300e616ed838be92b62f78f72a325745989e9d9f2cc3616e5d6254a9b02ed919561b757efd8f3a9d9317170890cfd5e8495937906939c42fa094af94b164ea7b5d624f28275e453cf1f3fa3d69b59984cb5456aa081e158ec889f2870173fd05847a6959bd239dea357212a8cf96757d3c3651f6519eac1117dd50e4be270c5c37285ef15a7289ffa1d87ac38d737199d3b3d2711305abf8f81b56ddd1e076fd3657a78a03da823ccbea542b3e4a4f684cb446e525ad56413e52f6a7e2f294bce40157e12c8b3c97f9044df563a9675d0555baf09d94bbb7eb0008f49fe8b78b295a11f728dd6f4269dcb3e7ba0cee9d48f16bc2d0c44f7ca3df763bb0bab58ac08009e5969dbd9c05b05d77397577368f72ddd175aa197c1d5e82142ec68d7adbdb3f4cbef5c2d47ebf5f3ae812f7bb8405b7e36fad7f01504b03040a000000000002732c4300000000000000000000000008001c00636f6d70696c652f555409000364b23152eca8335275780b000104f50100000414000000504b0304140000000800433ee24279b6c08e5c070000e411000009001c00636f6d70696c652f6355540900030d6ad2511aa8335275780b000104f50100000414000000c5576d53e34612fe8c7f45af70ddc10649b681ec2d5993781dd875158b293095da5b52419646f62c7abb9911c697cd7fcf3333926c0349ea2a499dabc0524ff733fdf274cf78fb853fe5993f0de4bcb54dfe5ffc31906fc7c3018db384678c8679a698543488529e71a944a07261b4089f615e2c059fcd15ed0c77a9d7e9ecbbbd4eb747d3251990aba5542ca59d691e06d23c7f374b039e78619eee1a94066a32e7920a91cf4490121e63c118c93c568b40b0235ae6258541468245da0b3e2d1523ae28c8223f1794e6118f970d1816ca2c6282d49c9162229594c7e6e5ddf935bd631913414217e534e1219df19065925180fdb544ce5984081a306d76aabdb9aabca1d31ce881e279b6478c635dd03d1312efb45f6f54a1eed15aba7602a503119417da7817de2f2909d4cadefbfda4ac628f8867669f795e20c4399011f48227094d199592c565b2d7e0c0827e184dde8faf273438ff483f0c2e2f07e7938fdfc042cd73acb27b66f1785a241cf0085404995a229e06e6c3c9e5f03dec066f4767a3c9478446a7a3c9f9c9d5159d8e2f69401783cbc968787d36b8a48bebcb8bf1d5894774c5b483ac41f99d12c4a694c870c4146822bdc6e823ea2fe16912d13cb867e041c8f83dfc0c280409ffb8bc0d5290e4d9ccc40d8b557ae1e828a62c577b24e1f09bb952c591ef2f160b6f96955e2e667e6291a47fecfd2dad7716a0d10c93518023ea75fdcf65b2f4eb8e420b8122dff19479a52cbc297805378a00ce335048926e1448da5df44d2942f653cc131d77bb47ecc1bed14ec4e2a04c148932f320d55dd8de2705d484a7a0d08ee56690ec9115a85ceb226d89c9b06005535cabc83ded558d976709aa90ad2b18ec034a83079e96a946c817a858ca50e225ed80c01fde2e315df61a90c36eefc3ae094b6f055e16a066555a594e532e4d93554400d1230e22e86e8097464961200422aa6c1b28c1542932502562da7a410bdd31f3a0284096088d1986b9883888012019c40ca9097775363b94dfe1ab0bdbb4d01964429896eee9b4e8bc3592f534b28790b18845101fa055511fa4b4d13c5c95ad917dfd54ed559dab2798ff0249c35270f4a79a0b1620527afdc4a15c8fa69f74d0861c98897759bec8748c7a221fd919821e00b5989dd8668214824996a9aa6bfff09368de9ad302a57a5481ba309fcb68c65aad320de41d753abd5eabc563fa444ebbeb50bf4f8e433f7ea335b3968664e13c2767952434e93461a963171f908c83fd56cc2dc60b7285057a0a8166404f63b06062eb79c5d53fa59120695100cc75c8030d9961cbbe46fb3bc0d33b3096dc82a408eb04fcac77dcde7ee9fd6233f15f5e38e406cfacfc7b74b17263abcca0092dad0483c8606e59f75f7aa1d36209c6de56b8d2d10a3a6911b9a561c0670c593af62376ef67250adf5b3d5befdadfea8d3bab4db555d9bfdd84b8b5f29995cfd6e5c81308570bbc0cb3362f0bf22ae736e0b27c9a47cbc7608dd442d9d735a09a054edb80d56c72f34a327bccaf2d5b3cd32414f3ccb43d4e4c518f3a190a5e28078a2ba29939824eb16b986977e8291819ca57f342dbeb6f7d35d19c37d3c61c340993faa0e1f7182033ce24d03402669aa80e6f3bc8728145cc350c4ad3b0184a5aecb564dcbf5dcc79386f36bb6d7d22f70131ca18a1d1972f041dbf94c25c0f6bad86c30f04c5e7282ce3dfe0b08de4318bbf362c0e1ed23e6675a7d3a9737fe0d08b475dbc5a71678a9cce6607198cf6cf07bf6810421daa2c4ba6942948612f6fcd44dd18d17aa99a09b507fb8febac4dfb879668e6b96d2a993038a3cc9cec53db7c7f45fb1da71948bda70309b9e85707a6c5b3a276cf382d5272636a43521f125c7938714cfd61b6f6b6de6021cafee6cdc9f8948ed7ecf4b57eed8a1f86fd9baaf2b330bca9ab7ed30ec3aaecd068ca0e95b58ad75a4f8aaee5ff53d55fe938433b612a5417835ee17ae58e7bbad73ccf3709309386dc246d99ad6e30408ef5e293c4b40c72a785f85be11cb71e7a7578b89907ecc845ffb65844b716cd1996e8876cd52d4b7d296e6b35878effd1b35ae69dbed00c371172ff438e5f8f1fe7f9a1a6e3b45b6dd84b5c0c1ce97b2f6f1a00df776ed7f238aa0fec67fcb1a86b25b644785c5e6c51e97fd224f241ead00f8b9267716eeb6b9453144a91ab34e943fbcfa8b6b6d7975d3c47a41966fe6948bce287977f073f59f21c20566359fdd7aa2d3d115cd125f7b48bd62437c3dfb59dade4beb32395dca187aa4b15e194c73a7e21e2d796c053d4d68d4d6e5a7dc77a4840aedab6e5dc49f5b04e0924628d2d1b1dd3d019813427d6aa702696d2066312b2d16144db1b0a3a278f6c10f1860978ae19698ad7b0b2299d158b1c8db2f2c616d0af956ce58ddbb8396cb6ff3ae3b620ecdf6a866ce854fc32a7a35179fdda48e09815569d30fe70313a1b4c46e3731a5dd1f978426f4f46e7ef68f8fe723c9e9c7c0f36d0e43d96aad501f4be3f199cd1d5c96402451c6fff9f4a9ba0dadf36e73658ef54f06b67b4ce4b237eacab1d7846d788b5ae1ec822d6d3caaf873a36b52754e7c9456068bc333fe7e9445f0a8ec8a857e7bf7e36a075b99e8cb2db3fb589c6edea0d56bbfd0a504b0304140000000800433ee2420dfbc7c056070000d61100000b001c00636f6d70696c652f63707055540900030d6ad2511aa8335275780b000104f50100000414000000c5576d53e34612fe8c7f45af70e5601749c640f696ac49bc0eecba8ac514984aed2d57419646f62c7abb9911c6c9e6bfe79919497e814beaea2e75ae024b3dddcff4cbd33de3ed17fe8467fe2490b3d636f9ffe38f817c371af46994253c6334c833c5a4a27e94f28c4b2502950ba345f80cf26221f874a66867b04bdd4ee7c0ed76f6bb34599001b95e48c552da99e46120cdf30fd334e08917e6e9ae4169a0c6332ea910f9540429e131168c91cc63350f043ba6455e5218642458a4bde0935231e28a822cf27341691ef178d18061a1cc222648cd1829265249796c5ede5fdcd07b9631112474594e121ed2390f59261905d85f4be48c4588a001d36667da9bebca1b3acb811e289e677bc438d6053d3021f14e07f54615ea1eada46b27503a104179a18d77e1fd8292402dedbd3f4eca32f6887866f699e505429c011941cf7992d0845129595c267b0d0e2ce8a7e1f8c3e8664cfd8b4ff453ffeaaa7f31fef41d2cd42cc72a7b60168fa745c2018f404590a905e269603e9e5e0d3ec0aeff6e783e1c7f426874361c5f9c5e5fd3d9e88afa74d9bf1a0f0737e7fd2bbabcb9ba1c5d9f7a44d74c3bc81a943f28416c4a890c474c8126d26b8c3ea1fe129e2611cd8207061e848c3fc0cf804290f0cfcbdb2005499e4d4ddcb058a6178e0e63ca72b547120ebf9d29551cfbfe7c3ef7a659e9e562ea271649fa27de5fd27ae7011acd30190538a6eebeffa54c167edd51682150e4079e32af94853701afe04611c079060a49d28d02497b1f7d538a90fd1cf344c7ddee127bb46fb413b138281345a2cc3c487517b60f480135e12928b463b919247b64052ad7ba485b62322c58c114d72a724f7b55e3e559822a64ab0a06fb90d2e091a765aa11f2392a96329478413b20f0c7770b4c975d13880607130b90b12aa62c272997a6adaad283da1147e935ffe1975152180181882adb064a30558a0ce48898b69ed35cf7c82c280ad023422b86612e220e2a004806314332c25d9dbf0ee5f7f8da876d5ae89c31214c1377752274a61ac96ae2d863c858c422880fd19ca80892d8681e2d0bd5c8be7daaf6bacece13ccbf83966129383a52cd040b1029bd79e250ae87d1cf3a6843074cc1fb2c9f673a463d838fedd400eb41266667b4991985609265aaead33ffd249aa9e67c40a9362a5017e64b194d59ab55a681bca74ea7db6db5784c9fc969ef3bd4eb91e3d03fbfd39a594b43b2709693b34c12da7292b0d4b18b8f48c6e1412be616e305b9c2023d8500fdd1c5182598d17a4271f5376924485a14007315f2504366d8b2a7d1fe0af0f41e8c25b72029c23a01bfea1db7b75f7abfd94cfcc20b87dce099957f0c2f976e6c951934a1a595601019cc2debfe4b2f745a2cc1a0db0a973a5a41272d22b7340cf882b14a277ec41efcac44e1bbcb67eb5dfb7bbd7167b9a9b62a7b77eb1077563eb5f2e9aa1c7902e16a819761bae665415ee5dc1a5c964ff268b109d6482d947d5d01aa59e0b40d58cd2637af24d34d7e6dd9e29926a19867a6ed71468a7ab8c950f04239505c12cdcc11748a5dc314bb474fc1c850be9a17da5e7febcb88e6bc9936e6684998d4470b7fc00099722681a61130d344755cdb41960b2c62ae61349a86c550d262af25e3dedd7cc6c359b3d95deb33b98f8851c6088dbe7e25e8f8a514e642586b351c7e24283e476119ff1b0edb483659fcad6171f098f68ef671c3ebd4b93f74e8c546172f57dca922a7b3de4106a3fdebe16f1a8450872acb9229650a52d8eb5a3351d746b45eaa6642edc1c1669db569efc812cd3cb74d251306679499933d6a9bef5774d0719a81d47d3a90908b5e75445a3c2b6a778dd3222537a63624f521c1958713c7d41f662b6fab0d16a2ec6fdf9e8ecee864c54e5fe4572ef561d8bbad2a3f7df5eab6aefa6d3b0cabb243a3293b54562a5e6b3d29ba96ff47557fade30ced84a9505d0c7a850b953beaea5ef33cdf24c04c1a7293b465b6bac50039d18b4f12d332c89d16e26f8533dc73e8f5d1d17a1eb02317bdbb621edd59346750a21fb265b72cf435b8add51c3af9a66bb5cc3b7da529ee1ee4fe8b1cbf1e3fcef3434dc769b75ab397b81838d2f75ede3600beefdcade471581fd8cff86351574a6c89b0595e6c51e97fd624f241ead00f8b9267716eeb6b9453144a91ab34e943fbcfa8b6b657975d3c47a41966fe6948bce2a7967f0f3f59f21c20566359fdd7aa2d3d115cb14feed93e5a93dc0c7f3776b692fbde8e5472071eaa2e5584531eebf84d88df57024f515b3736b969f51deb2101b96adb9673c7d5c32a25908815b6ac754c436704d29c58cbc299584a1b8c49c85a87116daf29e89c6cd820e23513f05c33d214af6165533a2b16391a65e98d2da05f2bd9ca1bb77173586fff55c66d41d8bbd30c59d3a9f8654e47a3f2e68d91c0312bac3a61f4f17278de1f0f471734bca68bd198de9d0e2fded3e0c3d568343efd116ca0f1072c55ab7de8fd78da3fa7ebd3f1188a38defe3f953641b5bf6fce6db0dea9e057ce689d9746bca9ab1d7846d788b5ae1ec822d6d3caaf873a36b52754e7c9456060bc333fe0e9545f0a8ec9a857e7bf7e36a075b99e8cb2bbff6a138dbbaf3758eef63b504b030414000000080002732c43b35fad82ec070000451300000c001c00636f6d70696c652f6a617661555409000364b231521aa8335275780b000104f50100000414000000b5586d53db4812fe8c7e4547b8362487256348722131bb8e171257114c81a9addc72b596a5913d416f3b9230decbfdf77b7a46920d26d96cdd9d3f18a9a7e7997e79ba7bccf613772a1377eae5736b9bdcfff14743be1b0dfa344a2299081aa44921f282fa412c139917ca2b52a5b5089f419a2d959ccd0bda193ca36ea7b3dfee76f6ba345d9206b95ce6858869679afa5eae9f7f9ac59e8c1c3f8d9f6994066a3c9739652a9d292f263c864a08cad3b058784a1cd2322dc9f7125222602be4b42c04c982bc24705345711ac870d98061a14c02a1a8980b2a848a734a43fdf2feec8ade8b44282fa2f3721a499f4ea52f925c9087f35992cf45000f1a30de76c2d65c56d6d0490a74af9069b24b42625dd1ad5039de69bf3ea842dda5b570ed78053ba228cd78f33358bfa4c82b56fb9d6f0765e57b4032d1e7ccd30c2ece810ca717328a682aa8cc455846bb0d0e76d02fc3f187d1d598fa679fe897fec545ff6cfce90d7614f314abe256183c196791043c1c555e522ce14f03f3f1f862f001fbfaef86a7c3f127b84627c3f1d9f1e5259d8c2ea84fe7fd8bf1707075dabfa0f3ab8bf3d1e5b1437429d840d1a07c2305a14e25221c880234c99d66d327e43f87a5514073ef568007be90b7b0d3231f24fcf3f436485e942633ed3776acc20b4387212569b14b390c7e3b2f8aecd075178b85334b4a275533373248b97be4fc5f4aefd443a16926230187d4dd733f97d1d2ad2b0a25048afc2463e19479e64cc12b989179305e80423971a140d2da43dd94ca17bf853262bf5b5d1277e68d7602117a6554902a13e7b3a7b80a5bfb540035923128b463b8e945bb640445caba085ba423ac44260ac92af92e5b55e3a549842c24eb0a1afb8062ef4ec665cc08e902198b0552bca41d10f8e3bb25bacb33ed0883838919c85825332fa7b1cc755955a907b50389d433ff6197562ad0023c15547b1b28258a522520472078f782165c23732fcb408f00a5e8fba90a24a800a0dc0b0542e43fe3f87528bdc19f3dec8d338e99504a17719703c1916a24eb811377be108108203e407122230862a3f96295a846f67253ed551d9d0dccbf83967ea9242ab2982be1c1537abd6150cacde837765ad3015df026491709fbc83df8d0740db01e6412a647eb9e9129918ba4a8eaf44f3f113355cf07a4ea4106eac47c2e8399b0ac32f6f21bea74ba5d4b86f42bd9ad3d9b7a3db26dfae71b564c2c4614fe3c257b152354e53412b16d16ef108b837d2b945682f51e6318b427d4560672130c758072464f41b3e656258ba7b996207a8107f475f003069701b54b1da8cfe83e74e406e2d64d4ac4a7bb7a3607b77e64273acdb15bbcabec4dee434c8c7c66e4b375394c445e6a8193a009a565468e252234abfb70493a4d83e543b0466aa0cceb1a103b6422aec1eaa8b7d34a327b98872d1337cd250a65a2ab03a344d53d20f795cc0a1b8aab8458f10d4a92da19e5caaf0ffc17a7697bfbb9f36f73c61f32c3c1de232bff189eaf195026d084162b6143a031f540ff066eb37b4b936382ab91e027d230e44009413261fd8a0aac800abc315a7e562df0f1909a00b72fc844528bfd393a35cb5e753a5aa0fb0e2acb04095def063588e8e912a9fa0b0792fff2e5856b4477273d8a2291f32892b76838332972a031027aa0aac6bb697ca9c222fa205aa92e703431163b561ef6268bb9f4e7cd6113eb576adf216a798820d1972f041db7cc95be40d65a4d1ddd11141f2ba33cfc4a1d194f1e56d24bcd0aee4abdd6414d087e45426705d99df55a8d0424856e613dd25af437daefd82611a4db6b6f8f1ff4d37ec750cebb8b7b2ff69a8e7260d313c36dcfbc3d3c486f68e91aaff77437bb10c621cfc85e351a6b1b6a71ababf7fb48446f922d82896502342891826495a025dfd45aac66d3d10f5d1c2e13433bad6f98f585103a30fca9f394dae1dea436ab56deb4ae81f98807630958f1f6edf1e8848eea59250b877f17acfd46f8ecdd7a7e4d0dfd52f3e2baa55f2b6e18bd861efa758d1b95f223fca857fe0a475eb1fd88ebca2eb56e956a6c52eb16a907f6a8af58a3feba2de22e4b554183d3fee5e5797ffca1e71c5eb79a37e47cd5ddf4c031295a1d5f07a15ab12a73ae31268ec871dc557a702fe1aae743b9c592436d4d08fbb9c3103698819844424f299eb16f2848d74fb86e41f83d2704b88d592a06bd78bd55d1d83211f26f43b12ea68679f4dcd281e9586056d5e65ebd78719f6186fa9ae43078863b1fb57f27dbade799fdf894e4a89bfab9b73fc785ccce5de7f97503e0baf6c4a8af911c85b94970e4c636d5c60c0ac9c5d5c177fdac9449981a1e69e5187c40c5157cb5f0cd9756b5b6d797db534e0a0f7bfdc59078c56f57f7063735113d0688d530afbe59d5b2b867b6d51e164d4f6b8f5ba6c9b54fba2f5e62a4ebef342f02dc993ad4c62f6cfc5a5578baaa464cfbbd99d16007a403b024c4cf6b8c9a76a0ffeac7b8795ccf3f62b2460d13b39a137501c1a7e69ab34a8e76ab347ee9d8dcbbf7106ddf53e0f03cd803e7ef6d01c999443a8f0d919a2c1ab14a519a2b6b4c2edd5ac95e8d7f14de3d67eeb16a0bc2de84c9724fc770a89afdacf2fab59634a3be6ae1a38fe7c3d3fe78383aa3e1259d8dc6f4ee7878f69e061f2e46a3f1f1cf20068d3f60a95aed43efe7e3fe295d1e8fc750b4adedad072d60ebfb48b099f0c7685193e07b92affd6cfdd8dcff50137605b97659e25035e287ba7ce823ba5accbadc5554c83dd1ad07170e3553b8b371a11c68ebf4ff4be8982f9787a4d5ab7b243f6bd03a831bad6cf25f1dc2b87ba6c9d7a7fd07504b0304140000000800433ee24202c9277e58070000de1100000b001c00636f6d70696c652f70617355540900030d6ad2511aa8335275780b000104f50100000414000000c5576d53e34612fe8c7f45af705d600f49c640364bd6245ec7ecba8ac51498ca6d965490a5913d416f3733c238d9fcf73c3323c93690a4aeee52e72ab0d4d3fd4cbf3cdd33de7ee14f79e64f03396f6d93ff3ffe18c8b7e3419fc659c23346833c534c2aea4729cfb8542250b9305a84cf202f9682cfe68a7606bbd4ed740edc6e67bf4bd3251990aba5542ca59d691e06d23c7f3b4b039e78619eee1a94066a32e7920a91cf4490121e63c118c93c568b40b0635ae6258541468245da0b3e2d1523ae28c8223f1794e6118f970d1816ca2c6282d49c9162229594c7e6e5ddf935bd631913414217e534e1219df19065925180fdb544ce5984081a306d76aabdb9aabca1d31ce881e279b6478c635dd03d1312ef74506f54a1eed15aba7602a503119417da7817de2f2909d4cadefbf3a4ac628f8867669f795e20c4399011f48227094d199592c565b2d7e0c082be1f4dde8faf27d43fff48dff72f2ffbe7938f5fc342cd73acb27b66f1785a241cf0085404995a229e06e6c3f072f01e76fdb7a3b3d1e42342a3d3d1e47c787545a7e34bead345ff72321a5c9ff52fe9e2faf2627c35f488ae98769035287f5282d89412198e98024da4d7187d44fd253c4d229a07f70c3c0819bf879f018520e15f97b7410a923c9b99b861b14a2f1c1dc594e56a8f241c7e3357aa38f6fdc562e1cdb2d2cbc5cc4f2c92f44fbcbfa5f5ce02349a61320a704cddaffca09cf97543a183c0906f79cabc5216de14b482174500df19182449f70924ed7db44d2942f653cc131d76bb4becc1bed14ec4e2a04c148932f320d54dd83e2005d484a760d08ea56690ec9115a85ceb226b8949b06005535cabc83ded558d9767098a90ad2b18ec434a83079e96a946c8172858ca50e125ed80bf1fde2e315cf61a90a3fdee875d1396de0ab42cc0ccaab2b29ca65c9a1eab78009e471c3cd0cd002f8d92c23c084454d9365082a95264604ac4b4f58216ba61e64151802b11fa320c731171f002403288195213eeea6c7628bfc3d73e6cd342679009613abaabd3a2f3d648d6d3c81e42c62216417c884e457d90d246f36855b646f6e553b55775ae9e607e058e86a5e0684f35172c40a4f4fa8943b99e4c3fe9a00d393012efb27c91e918f5403eb623042d006a313bb0cd002904932c5355d3fee527d1b43587054af5a80275617e2ea3196bb5ca349077d4e974bbad168fe91339ed7d877a3d721cfaf16bad99b534240be73939ab24a147a7094b1dbbf880641c1eb4626e315e902b2cd0530834035a1a7305035b8f2baebe904682a4450130d7210f3564862d7b1aedef004fefc058720b9222ac13f0abde717bfba5f79bcdc42fbc70c80d9e59f96174b17263abcca0092dad0483c8606e59f75f7aa1d36209a6de56b8d2d10a3a6911b9a561c0cf98b174e247ecdecf4a14bebb7ab6deb5bfd11b77569b6aabb277bb09716be5332b9fadcb912710ae167819466d5e16e455ce6dc065f9348f968fc11aa985b2af6b40350b9cb601abd9e4e69564f6985f5bb678a64928e699697b1c98a21e753214bc500e1457443373049d62d730d3eed053303294afe685b6d7dffa66a2396fa68d39671226f539c3ef3140669c49a06904cc34519ddd7690e5028b986b1894a6613194b4d86bc9b877bb98f370de6c76dbfa44ee0362943142a3cf9f093a7e2985b91dd65a0d871f088acf5158c67fc0611bc963167f69581c3ca43dccea4ea753e7fed0a1178fba78b5e2ce14399dcd0e3218ed5f0f7fd320843a5459964c295390c2dedd9a89ba31a2f55235136a0f0e1ed7599bf68e2cd1cc73db54326170469939d9a3b6f9fe271d749c6620759f0e24e4a2571d9816cf8ada5de3b448c98da90d497d4870e5e1c431f587d9dadb7a838528fb9b37c3f1299dacd9e95bfdda0d3f0c7b3755e5e322bca9ab7ed30ec3aaecd068ca0e95b58ad75a4f8aaee5ff51d55fe938433b612a54f75f0a7f57e48ebbe835cff34dfc66d0b4cc2e37981d27848527396919d04e0ba1b7c239ee3bf4eae8683305d88c8bde6db1886e2d9a3328d10ad9aa5196fa3adcd66a0e9dfca36bb5cc3b7da6192e21e4fe9b1cbf9e3ccef3f34c8768b7dab097b81338d2f75ede3400beefdcaea570549fd5cff86351d7aa6b39f0b8b2d8a2d2ffa4f9e383cfa11f1625cfe2dc96d628a7a81152ad34df43fbcfa8b6b6d7975d3c47a4c965fe6948bce227977f073f59f21c20566359fdd7aa2d3d0c5cb14feee93eba92dc0c7fd776ac92fbce4e5372071e0a2e5584031eebf86d88df59024f515bf734b969f51debf900b96adb6e7327d5c33a25908835b66c344bc36404d21c56abc299584a1b8c49c84673116d6f28e89c3cb241c41b26a0b866a4295ec3caa674562c72f4c8ca1b5b40bf56b295376ee3d2b0d9f9eb8cdb82b077ab19b2a153f1cb1c8c46e5f56b238163565875c2f8c3c5e8ac3f198dcf697445e7e309bd1d8ecedfd1e0fde5783c197e0736d0e43d96aad53ef4be1bf6cfe86a3899401127dbffa7d226a8f637cd910dd63b15fcdaf1acf3d2881feb6a079ed13562adab67b188f5a0f2eb798e4dede1d47972071818efcc0f791aeafbc03119f5eae8d7cf06b42ed7935176fb5f6da271f7f506abdd7e07504b03040a0000000000433ee2420000000000000000000000000c001c006465736372697074696f6e2f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242ff36f663470000005000000017001c006465736372697074696f6e2f616261636178692e74787455540900030d6ad2511aa8335275780b000104f501000004140000001dcab10dc0200c05d13e53fc2cc00e064cb00436326eb2ff224191ae7abae8b2718ace683218957771c9a2cf6fcb2d0f9e37245048911984551bcc41fac2cee468e69322a5747d504b0304140000000800433ee24281800a79350000004300000018001c006465736372697074696f6e2f70726f626c656d2e696e666f55540900030d6ad2511aa8335275780b000104f501000004140000004b4a2c4ecd4bcc4db54d4c4a4c4eacc8e44a2bcdc9010b8464a42a4005150a8af293725273b952528b93d33273e0aaf54a2a4ab800504b03040a0000000000433ee24200000000000000000000000006001c00696e7075742f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242b33924d3ab000000c02b00000d001c00696e7075742f6162616361786955540900030d6ad2511aa8335275780b000104f50100000414000000ed9a5b0a83301045ff03d9c35d4232498cd9ffc67a4d0b2ea094221c8e1c47e761fe44ccc8a985dad419ea875ae4549b7ad11c3a4275e574fa56578462a9574d07ce9d5a25a7b84a14557d68966bc271e874a239a7aee17699f71417abf872d89e955371e0c7dda75db73b6e729aae1e0e2f2f4d4f72eb3ed6f202eb1e000000004fc56ff6bfaf01000000bee4f38dae28a52084104208218410420821847e25feac0100003c9fbd01f605504b03040a0000000000433ee24200000000000000000000000007001c006c696d6974732f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee24271deea4ec20100007703000008001c006c696d6974732f6355540900030d6ad2511aa8335275780b000104f501000004140000006d934d6edc300c85f7730a16d9b4c0209909da437451f40cb445db6c24d1d04f3c93d39794c6cda4edce90c48f8fefd10f9f9e068e4f03e6e5f00065e10c74a1b1161c3c415ed07b905ad65af49220d63050029920d328d1d957e1409e0317e0d81e4d9c7201cf918e30496a6733bf528435896203a0566e5c16f018e78a33018ea324c77186225dc6c49e22063ad0b8087c557556f55144a2950a179698ad2ed5d8b0371dbde73ff26eca4d5f679f4f77f080170e552506a9b158934041d215566df9de6f8769b3d459f0598f7e0cd742f94be77e3b3fff076c7341e637fa6397d46456ec8c977bc6f9f4bc8fdec348546a8af046496c648e8e472c6a8ac084ec6bd2a12e3aeca9c5a94d1c21a89d65c1f26eacdaa4ac816e619383e1da9e632df2abba993e866926fe75ef648b5ed065c03dd7236401cca0ed6f7c936e8ba44a1d8daaa541f4b9b9d9a03d9a7d4d028e8bf9b02d94c838aa57538dba188ff07d02b13b8ca5c53df1a5f526479316b93be0d168b103540345a9f362355b62350b218f89575bd217eaae34b2ada4c9709459b1f08abed223fcd4b3b471d67db6672346182a7ba7b15c419743f3945521a545e16cbe3961e89ef7bf27dfe2e8c4c36f504b0304140000000800433ee24271deea4ec2010000770300000a001c006c696d6974732f63707055540900030d6ad2511aa8335275780b000104f501000004140000006d934d6edc300c85f7730a16d9b4c0209909da437451f40cb445db6c24d1d04f3c93d39794c6cda4edce90c48f8fefd10f9f9e068e4f03e6e5f00065e10c74a1b1161c3c415ed07b905ad65af49220d63050029920d328d1d957e1409e0317e0d81e4d9c7201cf918e30496a6733bf528435896203a0566e5c16f018e78a33018ea324c77186225dc6c49e22063ad0b8087c557556f55144a2950a179698ad2ed5d8b0371dbde73ff26eca4d5f679f4f77f080170e552506a9b158934041d215566df9de6f8769b3d459f0598f7e0cd742f94be77e3b3fff076c7341e637fa6397d46456ec8c977bc6f9f4bc8fdec348546a8af046496c648e8e472c6a8ac084ec6bd2a12e3aeca9c5a94d1c21a89d65c1f26eacdaa4ac816e619383e1da9e632df2abba993e866926fe75ef648b5ed065c03dd7236401cca0ed6f7c936e8ba44a1d8daaa541f4b9b9d9a03d9a7d4d028e8bf9b02d94c838aa57538dba188ff07d02b13b8ca5c53df1a5f526479316b93be0d168b103540345a9f362355b62350b218f89575bd217eaae34b2ada4c9709459b1f08abed223fcd4b3b471d67db6672346182a7ba7b15c419743f3945521a545e16cbe3961e89ef7bf27dfe2e8c4c36f504b0304140000000800433ee24271deea4ec2010000770300000b001c006c696d6974732f6a61766155540900030d6ad2511aa8335275780b000104f501000004140000006d934d6edc300c85f7730a16d9b4c0209909da437451f40cb445db6c24d1d04f3c93d39794c6cda4edce90c48f8fefd10f9f9e068e4f03e6e5f00065e10c74a1b1161c3c415ed07b905ad65af49220d63050029920d328d1d957e1409e0317e0d81e4d9c7201cf918e30496a6733bf528435896203a0566e5c16f018e78a33018ea324c77186225dc6c49e22063ad0b8087c557556f55144a2950a179698ad2ed5d8b0371dbde73ff26eca4d5f679f4f77f080170e552506a9b158934041d215566df9de6f8769b3d459f0598f7e0cd742f94be77e3b3fff076c7341e637fa6397d46456ec8c977bc6f9f4bc8fdec348546a8af046496c648e8e472c6a8ac084ec6bd2a12e3aeca9c5a94d1c21a89d65c1f26eacdaa4ac816e619383e1da9e632df2abba993e866926fe75ef648b5ed065c03dd7236401cca0ed6f7c936e8ba44a1d8daaa541f4b9b9d9a03d9a7d4d028e8bf9b02d94c838aa57538dba188ff07d02b13b8ca5c53df1a5f526479316b93be0d168b103540345a9f362355b62350b218f89575bd217eaae34b2ada4c9709459b1f08abed223fcd4b3b471d67db6672346182a7ba7b15c419743f3945521a545e16cbe3961e89ef7bf27dfe2e8c4c36f504b03040a0000000000433ee24200000000000000000000000007001c006f75747075742f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242cd21749470000000070100000e001c006f75747075742f6162616361786955540900030d6ad2511aa8335275780b000104f50100000414000000734bac4acd4b495430e4e5f2cccb4ccecc57305470cbcc5530e6e5e2e572834a1ac125cdc19216c8924095fe0a0545f929a525f9450ac5f90a25a965a94081d4acd2ccaa7c85bcd4e2924485348862647d2668361a214b9aa2499a214b9aa1491a224b9aa3495a5a5a224b5b60970600504b03040a0000000000433ee24200000000000000000000000004001c0072756e2f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee2424a41584c17060000ef0d000005001c0072756e2f6355540900030d6ad2511aa8335275780b000104f50100000414000000b5576b4f1b4714fd5cff8a1bc76a4165fd04d2923a8963850495003246118a2a58efce7a27ecce6c6766316e93ffde33b30fdb4012a96a2da4dd79dc735fe7debb3c7dd29971d199f93a6e3ca5ce7ffc7390af4fc7233a1509178cc65218a60d8dc2940bae8df28d54ee16e13796d952f1796c686bbc4dfd6e77e0f5bbbd3ecd96e440ce97dab094b66632f0b57b7f354f7d9eb403996e3b941a6a1a734d999273e5a784d74831465a4666e12b76404b9953e00b522cb456f0596e187143be083b52512a431e2d6b301ce422648a4cccc830956a92915bbc3db9a0b74c30e5277496cf121ed0310f98d08c7ce8b73b3a66213ca8c1acd8a1b5e6bcb4860e25d07dc3a5d821c671aee896298d350d2a4525ea0ead856bcb37d6114532b3c2dbb07e49896f56f2ed6f0765e57b485c383db1cce0620c6438bde049423346b966519eecd43890a00f47d377a717531a9d5cd287d164323a995e3e878489254ed92d2bf0789a251cf07054f9c22ce14f0df3fecd64fc0e72a3d747c747d34bb8468747d39337e7e774783aa1119d8d26d3a3f1c5f16842671793b3d3f3376da273660d6435ca375210b95422c22133a0896ed74297c8bf86a54948b17fcbc08380f15bd8e95300127e3fbd35929f4831777e4362155e187a1491906687340cfe2d36263be874168b457b2ef2b654f34e5220e9ce8bf6ff527ac73e0acd31190938a07ebfe3e7f34e5550a82030e4154f593bd7597b065ac18acc87ed0c0cd264eb043bad1eb13b7615f1c4fadcea8328596eeaf5800c20129e822e5bc5c34852b94060121743c53266b825a8deb18a4316f97962488a047116eb176c0db77649e4e98ca92b195dadc95e19796561b70ab2fbc98e2be0829da14bf58c19cb7d930b8e94b85aad6c73c87b94fa773ccd536b9b5c402a65a0c792b640fef7af97e84ceedefe837ba0349c26cdff62eef2efd5655cb77acaf392353a9fa55cbbfa2d39062b430e8ed9424378dc25835ee3ab0abb8652cce44a808521b3d20b5ad8628cfd2c030f43d47c1048153a0725693f62c84eb06d33d5257983470fb26986f41053ca758bbecd870d45bdb39e3576173016b210dbbb482e2288e0d637f75694a8f7f61f5e7b5685f201e62fe07f902b8ed237b1623e3ca55f1f18246dd7bbb24e3be2a1ddde08b910d647dbec0f8af684f2026d59310c5c73ca14d34c98b2217cf797d892708308a9ba97812a319ff270ce1a8d3cf5f50d75bbfd7e8387e4e5ce8e4fe822f4a213b2db8ec8a1bebf7a6ff0883e52eb250d87c8c41fcf2d9668fc60a5f2e1f526c475b13f2ff6e7ebfb416cddae36da02cd44e619b51b2c41d3d984137226c3e57db07ab7802a966b40112f4c6db61c58d31adc6c9227cb9d79b553fbc080444d972a8ab870e443d1a9aad275a078669ab87887dcef0eac0ac766e4ab384341dd20b31072812f596be5edd3ce5e1b79c779d74913a66d27e5b7a0f19c330d348b80ca52e5742aca492a1ca2ba50a58e36280dbbdd6ee86878bd887910d7caae1b1fc9bb838f3a826bf4f933e14e27d7ca7dff54b71a556c7a1b71e96fac06f72364a9550469552c9803b384a5cde270151887ffa430a5f718041a2ec6061a1a3e0ad0ed941dd476a31cd0a82565198f006cdb89c9cd4fc57911491f4ad775eeaeeb54852b8fe8ecff3b9da8e7f0bec6bd4d2f11efc714ea684de3f7ddd8779cb22d63d81a5439b24ba4646ea8d95df72a61d831aebf0cc9dda29f69d06d163544aef70d7bf6c5bd0dba0e5c80ca1adb25f86e939edc4bf2eae4a14ea242beb58b05e0f08721920ef77af88ced56a07b5f05dd7b1cd461b4fedefb6241d680a361afdbdfad60f7bf0abbff55d808b0fb5f6acc469015e4d006f5dddde86cee085c85836da467e3ac613f04ea93cf34c7c8268fff494db457c3034c503b14c40d0b9b95b92f0b73376c2a68319a49656c7b9931b426740b144349c547e02a723c2b1c003b87d7d922bc6e1460e3156b5dab585aa896bd469e4741aca474ba30a6b969d28b1ffb0d4b4befb0d7252f6ad918a1645a2eafe4891e7993527adc26efa2689fe4bd2dba265a034287510261fc8783ff1614de420b939297964f53d0d19bb60a7e7abc8c77bb53c6b0810f8061eb65394eb07009ecad8d147ba15b1799670759a17715cdc037d5aebde8c264b11aff00504b0304140000000800433ee2424a41584c17060000ef0d000007001c0072756e2f63707055540900030d6ad2511aa8335275780b000104f50100000414000000b5576b4f1b4714fd5cff8a1bc76a4165fd04d2923a8963850495003246118a2a58efce7a27ecce6c6766316e93ffde33b30fdb4012a96a2da4dd79dc735fe7debb3c7dd29971d199f93a6e3ca5ce7ffc7390af4fc7233a1509178cc65218a60d8dc2940bae8df28d54ee16e13796d952f1796c686bbc4dfd6e77e0f5bbbd3ecd96e440ce97dab094b66632f0b57b7f354f7d9eb403996e3b941a6a1a734d999273e5a784d74831465a4666e12b76404b9953e00b522cb456f0596e187143be083b52512a431e2d6b301ce422648a4cccc830956a92915bbc3db9a0b74c30e5277496cf121ed0310f98d08c7ce8b73b3a66213ca8c1acd8a1b5e6bcb4860e25d07dc3a5d821c671aee896298d350d2a4525ea0ead856bcb37d6114532b3c2dbb07e49896f56f2ed6f0765e57b485c383db1cce0620c6438bde049423346b966519eecd43890a00f47d377a717531a9d5cd287d1');
SELECT pg_catalog.lowrite(0, '\x64323a995e3e878489254ed92d2bf0789a251cf07054f9c22ce14f0df3fecd64fc0e72a3d747c747d34bb8468747d39337e7e774783aa1119d8d26d3a3f1c5f16842671793b3d3f3376da273660d6435ca375210b95422c22133a0896ed74297c8bf86a54948b17fcbc08380f15bd8e95300127e3fbd35929f4831777e4362155e187a1491906687340cfe2d36263be874168b457b2ef2b654f34e5220e9ce8bf6ff527ac73e0acd31190938a07ebfe3e7f34e5550a82030e4154f593bd7597b065ac18acc87ed0c0cd264eb043bad1eb13b7615f1c4fadcea8328596eeaf5800c20129e822e5bc5c34852b94060121743c53266b825a8deb18a4316f97962488a047116eb176c0db77649e4e98ca92b195dadc95e19796561b70ab2fbc98e2be0829da14bf58c19cb7d930b8e94b85aad6c73c87b94fa773ccd536b9b5c402a65a0c792b640fef7af97e84ceedefe837ba0349c26cdff62eef2efd5655cb77acaf392353a9fa55cbbfa2d39062b430e8ed9424378dc25835ee3ab0abb8652cce44a808521b3d20b5ad8628cfd2c030f43d47c1048153a0725693f62c84eb06d33d5257983470fb26986f41053ca758bbecd870d45bdb39e3576173016b210dbbb482e2288e0d637f75694a8f7f61f5e7b5685f201e62fe07f902b8ed237b1623e3ca55f1f18246dd7bbb24e3be2a1ddde08b910d647dbec0f8af684f2026d59310c5c73ca14d34c98b2217cf797d892708308a9ba97812a319ff270ce1a8d3cf5f50d75bbfd7e8387e4e5ce8e4fe822f4a213b2db8ec8a1bebf7a6ff0883e52eb250d87c8c41fcf2d9668fc60a5f2e1f526c475b13f2ff6e7ebfb416cddae36da02cd44e619b51b2c41d3d984137226c3e57db07ab7802a966b40112f4c6db61c58d31adc6c9227cb9d79b553fbc080444d972a8ab870e443d1a9aad275a078669ab87887dcef0eac0ac766e4ab384341dd20b31072812f596be5edd3ce5e1b79c779d74913a66d27e5b7a0f19c330d348b80ca52e5742aca492a1ca2ba50a58e36280dbbdd6ee86878bd887910d7caae1b1fc9bb838f3a826bf4f933e14e27d7ca7dff54b71a556c7a1b71e96fac06f72364a9550469552c9803b384a5cde270151887ffa430a5f718041a2ec6061a1a3e0ad0ed941dd476a31cd0a82565198f006cdb89c9cd4fc57911491f4ad775eeaeeb54852b8fe8ecff3b9da8e7f0bec6bd4d2f11efc714ea684de3f7ddd8779cb22d63d81a5439b24ba4646ea8d95df72a61d831aebf0cc9dda29f69d06d163544aef70d7bf6c5bd0dba0e5c80ca1adb25f86e939edc4bf2eae4a14ea242beb58b05e0f08721920ef77af88ced56a07b5f05dd7b1cd461b4fedefb6241d680a361afdbdfad60f7bf0abbff55d808b0fb5f6acc469015e4d006f5dddde86cee085c85836da467e3ac613f04ea93cf34c7c8268fff494db457c3034c503b14c40d0b9b95b92f0b73376c2a68319a49656c7b9931b426740b144349c547e02a723c2b1c003b87d7d922bc6e1460e3156b5dab585aa896bd469e4741aca474ba30a6b969d28b1ffb0d4b4befb0d7252f6ad918a1645a2eafe4891e7993527adc26efa2689fe4bd2dba265a034287510261fc8783ff1614de420b939297964f53d0d19bb60a7e7abc8c77bb53c6b0810f8061eb65394eb07009ecad8d147ba15b1799670759a17715cdc037d5aebde8c264b11aff00504b0304140000000800700b2e43f347839861080000fa14000008001c0072756e2f6a6176615554090003649f3352b1a8335275780b000104f50100000414000000cd577f73dbb811fddbfa141b59d373ae9628cb3fd2f8a2dc298a1c6bceb13292dc6be6d2b12812941893044b82967549be7bdf022445d94ed276da99ca1e8900160f8bddf716e0ee136bee47d6dc4e97b55db2fecb1f0df96ad4efd1280afc48505f464aa48a7a6ee8477eaa125bc9445b113e7d19af137fb154b4d77f4a9d76fbb0d9691f7468be260d3259a74a84b437978e9deae75f16a1ed072d47864f354a09355dfa29c5895c24764878f4122128959e5ad98938a5b5ccc8b1234a84cb5ef8f34c09f215d9916bc98442e9fadeba04c34016b92221b514a44412a6243ddd787379456f4424123ba077d93cf01dbaf01d11a5826caccf3de952b8d84109c6d3ced89b49ee0d9d49a0dbca97d13e091fe309dd8a24459b0e8b8572d47daa846bcf56bc918464cc939fc2fb3505b6dacc6f7d3b289bbdbbe4477a9da58cb1c52590b1e9951f04341794a5c2cb82fd120733e8b7e1f47c7435a5dee57bfaad371ef72ea7ef7fc20cb5941815b7c2e0f9611cf880c746133b526beca784793b18f7cf31aff76a78319cbec7d6e86c38bd1c4c2674361a538fdef5c6d361ffeaa237a67757e377a3c9a0453411eca02851be91024fa712117685024dd25639e93df29fc2d3c0a5a57d2bc00347f8b7f0d3260724fc7e7a4b243b90d142ef1b3336e185a3438f22a9f62985c32f964ac5a796b55aad5a8b286bc96461050629b55eb6fe27d2bbb02134cd6424e0940e8eac54c4ca2a1405098122bff8a1686569dc9a83577023b6e1bc00855262a1a0a77140d05874ed0498c0ed0eb81267eadaf3030e43e3901440023f0463f6cc8f929464116213e830262216ca678ea6fbbcb42b3c3b0b14c92840a8a3aa01cbb871445116ce45722dbdebcadc6b25af1976cff0dd0ef6b5860d415d9dedb9504c7f95453eb2a2e55af8a6918fb1973b3fcc42f64dae302b1460c89af6c0ffb7afd6284edaeee4811d588d4d53eaff21b4f1af8531cc799d7c3c274e9acd433fd512ce69062f5d1f3463ad213cda48a1dcd849815d42254265490422ba8267af68c57a5cda710c2aba90bde3c8c4d51b9494da9e1077c279cab96a93bcc1cf01e68631d243224974c1e8703e3814654f356be2ce11c2152eba8f905c4410c12d2d8f37a428fb4e1e9a3d2b42f900f32f908093253ed4af9689b0b1537afec021c985ef9a37ada9878a7b13c955c47be47a7f6a2a141406e20a731ee8fa1427221591ca6bc2773f01ab429f4548d5bd0c1489f998b90b51ab65a19dde50bbdde9d47c979a99f6e3230a09bdb45c716b451996ef6c9e6bbe47bf53e367ea769189bfffc458516d876765ddd936c4ccf42f4cffa2daef2c79db45472b423d91594cad9a085077b6e1223997eefa3e58d96ba04cb302e4f9c6d57a4383d5d9e17a9d9a32ef59143de51e0490a8ae53459e1f69f2417449a1f4d449fc58d5617887dc1f1df2129acdc8971983a06e90594cd281cf59cbf3f9978f5f8ebce6bc2ea68148b998fab7a0f1c2175c791801ca4af203cac849261884baa0524d1b4883bb5bb5d4ebce564bdf59968bcd6abf53f30e7b4c3d6c8d3e7f26d858599ae82b5061552b6273b01597ce56ebf07e84985a26481bb1e028980722ac9bc14d60347e33e1c0b5b062052309a9e9e9fe8f7682b6139746456765be76f19b93b5c5fd994f8ab5d159996ebc1f8cc7a3f129716d2f6d708aa1b8e28e424d8a25aadabca82ce442d6a0822937fa0ac3bbd57b3de0052384a23bc3ed52f093f66756c4f71377edeefed8fa6282a90f18c4d77e64ac7fd19b4c1e8b36ae1e97c3cb37a7289aa1bc655f5a1ac7ec40dc291cb1daab0de7bfb26ec9f5477cce4167df40b9e7e1e328da68b69d094dad871bc331bb89fb1e420d2571477e4f433d4db8ea41044ff9e2e4ab1fcc38eaab6b234155d61d5757bc23f0ffb10553afb26215d208f43ee889d63897f06ee3b0880a3791c285a27abbbaab40a047e97adf256d457fa6c376ddd434d26751f7801ff4d3615b834760608aee1cfca84e4fee896e33f2704d2233bf718406e0f08f433dec1e1fe0cda25d801e7f15f4f871508dd1f874fc85412ac05ef7a0dd392a604fbe0a7bf255580fb0275f4acc5a29685fb57027d0b5b17adc6881833ca9423d6e6f0dd51c10a33b8b57eeac66d2dbdf104657cd3513aac166757af9a78eb1d26dfa4c0bdcbaa8f90faa5bc531547ffc7063cfcd525bf3f93a564fadd68f1f4a00cb82f2b5394af78b1783d119bdccf7c6af9e95d75007738d635caf3db250471dcb89333ff2a4a9dbda380451153515d759c77c69d3da6e75b88967973832fa8b21d1c4dba37583fb8b081e03c4a897e6df6c5afb68dfdac569c2cfc549f2a1c1adfc30d146e571c2ad8ae68ce523b2cb07fe1de53d637688bb58268a744579d79b9e775ba72d2bafd8a78db2bbc6b26e260d2d05eccd68af396d183136fd9c3ccdb3cef1096e14fa5ba2133722f4e25d1defbd099eaecc5d819a6fcc15819a3cabdf4286584e7876f5af7e0c378fcd62ebcdbf85778d4fac9e2fbf72232d1bf50697c87a0d1cbfb583ee87c6cf868d1f1aa6a7244a2e8232ac48647925db4456873f33c9d484d85206d1ee960173e2de1c647c6b0aa20dba1af22ef14245cf8e8f37d435dd8944ba36de18025b85112e469543bf22e72a1f76d0d99db13cb66c708d83033b79f540e783cb597e3b1b68b2f02b87399b9b704b383764674af28d1687639d8d35879e6bd4bcfa165cec8f5e0fe87234a557039ca9d43f1f8f46d3c1eb16bd1ee9eef1d5254dcf87131af1ef80def6869734198cff3a189b22f2ff09b5f350be3bf94df0dbf2dda99c995bf22d42feaf6ab788fa3393cbff44930ff5f7984a0b4d7e4d8bb914f37b255feebe2f4bcd4b68b2b8433007f51176d0beff82f092aa7f9732fa432472eb7db67283dc7a01c4c0f087b7f4e66a309970968753424691dae9f0ed80f4ad945e6cffe59e3ddf5c89f985ce8464a32bd653de6bca26f2c03ba8fd13504b03040a0000000000433ee24200000000000000000000000006001c0074657374732f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242e3d65566820100008902000007001c0074657374732f6355540900030d6ad2511aa8335275780b000104f501000004140000006550d16adc30107cd7574c74574820f1f90aa10f771728d4854020e1ce6f69203a59aed5d89291d66942e9bf772d5fda0b7dd2eeecccce8e66278bbd758bbd8a8d988919cac646d4b635e84da87de82214c84482af418d811ac8ff18aaef06f13592e9325c13b4720883c3cf4691793601af7e406d4c0bdbf53e907204f2d39e774bd851b92a615107db53f28e0c28820a06d6e976a84cc54562f5c1ef5bd3a157fa49b15e68265ea5cd99c67a5ddc7e15b383681da9b23e6bae8465ff4e59777a865f02d08d0a88f7cb3c7f58711bf9fcfa547e88f21cf16c44fac08a047d73f27cc282a12138e42bf15b8c2e47c67cdbe46c5d3f50a5482586286f361fc5b6b8db2c73a19bce57f87479892c5b68dff59c73a1c571f396223de6c5605edebcd7f11f4f9a54fc23be5dc102ccd950d81af7b8e09054f981723cacc6df731c835bbe71f3389e7f983e329c04723e4d254e36907fc3c823396074e3218bedf6762b27e0c512965cd65698361af11fe7c018e7695016bb12779f77bbe28b1469988b3f504b01021e030a0000000000433ee242000000000000000000000000080018000000000000001000ed4100000000636f6d706172652f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242a9caa15889040000cb0e0000090018000000000001000000ed8142000000636f6d706172652f6355540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242a9caa15889040000cb0e00000b0018000000000001000000ed810e050000636f6d706172652f63707055540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242a9caa15889040000cb0e00000c0018000000000001000000ed81dc090000636f6d706172652f6a61766155540500030d6ad25175780b000104f50100000414000000504b01021e030a000000000002732c43000000000000000000000000080018000000000000001000ed41ab0e0000636f6d70696c652f555405000364b2315275780b000104f50100000414000000504b01021e03140000000800433ee24279b6c08e5c070000e4110000090018000000000001000000a481ed0e0000636f6d70696c652f6355540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee2420dfbc7c056070000d61100000b0018000000000001000000a4818c160000636f6d70696c652f63707055540500030d6ad25175780b000104f50100000414000000504b01021e0314000000080002732c43b35fad82ec070000451300000c0018000000000001000000a481271e0000636f6d70696c652f6a617661555405000364b2315275780b000104f50100000414000000504b01021e03140000000800433ee24202c9277e58070000de1100000b0018000000000001000000a48159260000636f6d70696c652f70617355540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee2420000000000000000000000000c0018000000000000001000ed41f62d00006465736372697074696f6e2f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242ff36f6634700000050000000170018000000000001000000a4813c2e00006465736372697074696f6e2f616261636178692e74787455540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee24281800a793500000043000000180018000000000001000000a481d42e00006465736372697074696f6e2f70726f626c656d2e696e666f55540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000060018000000000000001000ed415b2f0000696e7075742f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242b33924d3ab000000c02b00000d0018000000000001000000a4819b2f0000696e7075742f6162616361786955540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000070018000000000000001000ed418d3000006c696d6974732f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee24271deea4ec201000077030000080018000000000001000000a481ce3000006c696d6974732f6355540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee24271deea4ec2010000770300000a0018000000000001000000a481d23200006c696d6974732f63707055540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee24271deea4ec2010000770300000b0018000000000001000000a481d83400006c696d6974732f6a61766155540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000070018000000000000001000ed41df3600006f75747075742f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242cd21749470000000070100000e0018000000000001000000a481203700006f75747075742f6162616361786955540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000040018000000000000001000ed41d837000072756e2f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee2424a41584c17060000ef0d0000050018000000000001000000ed811638000072756e2f6355540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee2424a41584c17060000ef0d0000070018000000000001000000ed816c3e000072756e2f63707055540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800700b2e43f347839861080000fa140000080018000000000001000000ed81c444000072756e2f6a6176615554050003649f335275780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000060018000000000000001000ed41674d000074657374732f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242e3d655668201000089020000070018000000000001000000ed81a74d000074657374732f6355540500030d6ad25175780b000104f50100000414000000504b0506000000001a001a00230800006a4f00000000');
SELECT pg_catalog.lo_close(0);

SELECT pg_catalog.lo_open('16605', 131072);
SELECT pg_catalog.lowrite(0, '\x504b03040a0000000000433ee24200000000000000000000000008001c00636f6d706172652f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242a9caa15889040000cb0e000009001c00636f6d706172652f6355540900030d6ad2511aa8335275780b000104f50100000414000000b5575153db38107ecfaf580c0f3093c410eeee811ebd065adacc70c084301de67ad32ab61c8bca92cf9231f9f7f7494e9ccc11d2d003bfd892bc9ff6fb76575e6f6f8563a1c23133696b9bc217be3ce4c9e5699f2e95148ad3a956961b4bfd38134a185b30ab0bff16e13ad5f9b41093d4d2eee91ef5f6f70f3bbdfd831e8da7e441def37b2e759e716569c45946bb631d3133359667ef261913b21be96ccfe335a0a35418ca0b3d296080c7a4e09c8c4e6cc50a7e44535d52c414153c76fe88716939094b4cc5a12e28d3b148a60d18164a15f3826ccac9f22233a4133ff87871431fb9e2059374558ea588e85c445c194e0cfbbb1993f2185c1a306776e6bcb99e7943671ae8cc0aaddac405d60bbae785c1980ee71bcd50dbb424dc2eb38e48413a77c67bf07e4a92d9857d77bd280bee3109e5f749750e8a299041ba1252d29853697852ca7683030bfa3c187dbabc1951ffe2963ef787c3fec5e8f60d2c6caab18a90d57822cba5003c88164cd929f834307f7e189e7e825dff64703e18dd821a9d0d46171faeafe9ec72487dbaea0f4783d39bf3fe90ae6e865797d71fba44d7dc39c81b943521487c28a170cc2dd2c4741ba35bc4dfc0531953caee39f220e2e21e7e328a908e3f0e6f83c4a45613cf1b160b79e1e82021a56d9b0c1cfe3db5363f0ac3aaaaba135576753109658d64c2b7ddd72a423a67283b9fcd2e08bd83f0ae94e1bcba504448927722e3ddd2e4dd31320b563e494c5488dcce65314798df3940eab3ec2bc29b97d64df4505072697ce8c88f25cfbe0a8529dacd7c55d709e46a00ef67a8c29c47f027a228e5d177246a9b90f2befcb03324dbf37ef8da2f388b8d0f85744cfc69328b8db1285656c4d4388031e5e0e490aca6bb329e70032c91d05fb4459d82829d83803a7a3ee805f4f71b07a65a443c4a3505570cc1e3ae84665c02b7f400c45f0e5b8968619b0bfe507b323b051672dd95f01167518eaa36cb6a79d71662b94a62d255ca249dd1ce356281fd9ce351cad4c42779e6863e831daae40f282c264b7f56209b5b886b429d7f6a5e9ecfdb30e6f7a12a51b8bdc5732d41b0f34740c7c714ec3fe2dde11478b02f80fa12b85b0fb76d4a8404958cd9280d1a91ce1693d08645564e17323995e67e75c62fe85a2772789b38b86cb2fafd185fa419818e2fd03a300e616ed838be92b62f78f72a325745989e9d9f2cc3616e5d6254a9b02ed919561b757efd8f3a9d9317170890cfd5e8495937906939c42fa094af94b164ea7b5d624f28275e453cf1f3fa3d69b59984cb5456aa081e158ec889f2870173fd05847a6959bd239dea357212a8cf96757d3c3651f6519eac1117dd50e4be270c5c37285ef15a7289ffa1d87ac38d737199d3b3d2711305abf8f81b56ddd1e076fd3657a78a03da823ccbea542b3e4a4f684cb446e525ad56413e52f6a7e2f294bce40157e12c8b3c97f9044df563a9675d0555baf09d94bbb7eb0008f49fe8b78b295a11f728dd6f4269dcb3e7ba0cee9d48f16bc2d0c44f7ca3df763bb0bab58ac08009e5969dbd9c05b05d77397577368f72ddd175aa197c1d5e82142ec68d7adbdb3f4cbef5c2d47ebf5f3ae812f7bb8405b7e36fad7f01504b0304140000000800433ee242a9caa15889040000cb0e00000b001c00636f6d706172652f63707055540900030d6ad2511aa8335275780b000104f50100000414000000b5575153db38107ecfaf580c0f3093c410eeee811ebd065adacc70c084301de67ad32ab61c8bca92cf9231f9f7f7494e9ccc11d2d003bfd892bc9ff6fb76575e6f6f8563a1c23133696b9bc217be3ce4c9e5699f2e95148ad3a956961b4bfd38134a185b30ab0bff16e13ad5f9b41093d4d2eee91ef5f6f70f3bbdfd831e8da7e441def37b2e759e716569c45946bb631d3133359667ef261913b21be96ccfe335a0a35418ca0b3d296080c7a4e09c8c4e6cc50a7e44535d52c414153c76fe88716939094b4cc5a12e28d3b148a60d18164a15f3826ccac9f22233a4133ff87871431fb9e2059374558ea588e85c445c194e0cfbbb1993f2185c1a306776e6bcb99e7943671ae8cc0aaddac405d60bbae785c1980ee71bcd50dbb424dc2eb38e48413a77c67bf07e4a92d9857d77bd280bee3109e5f749750e8a299041ba1252d29853697852ca7683030bfa3c187dbabc1951ffe2963ef787c3fec5e8f60d2c6caab18a90d57822cba5003c88164cd929f834307f7e189e7e825dff64703e18dd821a9d0d46171faeafe9ec72487dbaea0f4783d39bf3fe90ae6e865797d71fba44d7dc39c81b943521487c28a170cc2dd2c4741ba35bc4dfc0531953caee39f220e2e21e7e328a908e3f0e6f83c4a45613cf1b160b79e1e82021a56d9b0c1cfe3db5363f0ac3aaaaba135576753109658d64c2b7ddd72a423a67283b9fcd2e08bd83f0ae94e1bcba504448927722e3ddd2e4dd31320b563e494c5488dcce65314798df3940eab3ec2bc29b97d64df4505072697ce8c88f25cfbe0a8529dacd7c55d709e46a00ef67a8c29c47f027a228e5d177246a9b90f2befcb03324dbf37ef8da2f388b8d0f85744cfc69328b8db1285656c4d4388031e5e0e490aca6bb329e70032c91d05fb4459d82829d83803a7a3ee805f4f71b07a65a443c4a3505570cc1e3ae84665c02b7f400c45f0e5b8968619b0bfe507b323b051672dd95f01167518eaa36cb6a79d71662b94a62d255ca249dd1ce356281fd9ce351cad4c42779e6863e831daae40f282c264b7f56209b5b886b429d7f6a5e9ecfdb30e6f7a12a51b8bdc5732d41b0f34740c7c714ec3fe2dde11478b02f80fa12b85b0fb76d4a8404958cd9280d1a91ce1693d08645564e17323995e67e75c62fe85a2772789b38b86cb2fafd185fa419818e2fd03a300e616ed838be92b62f78f72a325745989e9d9f2cc3616e5d6254a9b02ed919561b757efd8f3a9d9317170890cfd5e8495937906939c42fa094af94b164ea7b5d624f28275e453cf1f3fa3d69b59984cb5456aa081e158ec889f2870173fd05847a6959bd239dea357212a8cf96757d3c3651f6519eac1117dd50e4be270c5c37285ef15a7289ffa1d87ac38d737199d3b3d2711305abf8f81b56ddd1e076fd3657a78a03da823ccbea542b3e4a4f684cb446e525ad56413e52f6a7e2f294bce40157e12c8b3c97f9044df563a9675d0555baf09d94bbb7eb0008f49fe8b78b295a11f728dd6f4269dcb3e7ba0cee9d48f16bc2d0c44f7ca3df763bb0bab58ac08009e5969dbd9c05b05d77397577368f72ddd175aa197c1d5e82142ec68d7adbdb3f4cbef5c2d47ebf5f3ae812f7bb8405b7e36fad7f01504b0304140000000800433ee242a9caa15889040000cb0e00000c001c00636f6d706172652f6a61766155540900030d6ad2511aa8335275780b000104f50100000414000000b5575153db38107ecfaf580c0f3093c410eeee811ebd065adacc70c084301de67ad32ab61c8bca92cf9231f9f7f7494e9ccc11d2d003bfd892bc9ff6fb76575e6f6f8563a1c23133696b9bc217be3ce4c9e5699f2e95148ad3a956961b4bfd38134a185b30ab0bff16e13ad5f9b41093d4d2eee91ef5f6f70f3bbdfd831e8da7e441def37b2e759e716569c45946bb631d3133359667ef261913b21be96ccfe335a0a35418ca0b3d296080c7a4e09c8c4e6cc50a7e44535d52c414153c76fe88716939094b4cc5a12e28d3b148a60d18164a15f3826ccac9f22233a4133ff87871431fb9e2059374558ea588e85c445c194e0cfbbb1993f2185c1a306776e6bcb99e7943671ae8cc0aaddac405d60bbae785c1980ee71bcd50dbb424dc2eb38e48413a77c67bf07e4a92d9857d77bd280bee3109e5f749750e8a299041ba1252d29853697852ca7683030bfa3c187dbabc1951ffe2963ef787c3fec5e8f60d2c6caab18a90d57822cba5003c88164cd929f834307f7e189e7e825dff64703e18dd821a9d0d46171faeafe9ec72487dbaea0f4783d39bf3fe90ae6e865797d71fba44d7dc39c81b943521487c28a170cc2dd2c4741ba35bc4dfc0531953caee39f220e2e21e7e328a908e3f0e6f83c4a45613cf1b160b79e1e82021a56d9b0c1cfe3db5363f0ac3aaaaba135576753109658d64c2b7ddd72a423a67283b9fcd2e08bd83f0ae94e1bcba504448927722e3ddd2e4dd31320b563e494c5488dcce65314798df3940eab3ec2bc29b97d64df4505072697ce8c88f25cfbe0a8529dacd7c55d709e46a00ef67a8c29c47f027a228e5d177246a9b90f2befcb03324dbf37ef8da2f388b8d0f85744cfc69328b8db1285656c4d4388031e5e0e490aca6bb329e70032c91d05fb4459d82829d83803a7a3ee805f4f71b07a65a443c4a3505570cc1e3ae84665c02b7f400c45f0e5b8968619b0bfe507b323b051672dd95f01167518eaa36cb6a79d71662b94a62d255ca249dd1ce356281fd9ce351cad4c42779e6863e831daae40f282c264b7f56209b5b886b429d7f6a5e9ecfdb30e6f7a12a51b8bdc5732d41b0f34740c7c714ec3fe2dde11478b02f80fa12b85b0fb76d4a8404958cd9280d1a91ce1693d08645564e17323995e67e75c62fe85a2772789b38b86cb2fafd185fa419818e2fd03a300e616ed838be92b62f78f72a325745989e9d9f2cc3616e5d6254a9b02ed919561b757efd8f3a9d9317170890cfd5e8495937906939c42fa094af94b164ea7b5d624f28275e453cf1f3fa3d69b59984cb5456aa081e158ec889f2870173fd05847a6959bd239dea357212a8cf96757d3c3651f6519eac1117dd50e4be270c5c37285ef15a7289ffa1d87ac38d737199d3b3d2711305abf8f81b56ddd1e076fd3657a78a03da823ccbea542b3e4a4f684cb446e525ad56413e52f6a7e2f294bce40157e12c8b3c97f9044df563a9675d0555baf09d94bbb7eb0008f49fe8b78b295a11f728dd6f4269dcb3e7ba0cee9d48f16bc2d0c44f7ca3df763bb0bab58ac08009e5969dbd9c05b05d77397577368f72ddd175aa197c1d5e82142ec68d7adbdb3f4cbef5c2d47ebf5f3ae812f7bb8405b7e36fad7f01504b03040a000000000002732c4300000000000000000000000008001c00636f6d70696c652f555409000364b23152eca8335275780b000104f50100000414000000504b0304140000000800433ee24279b6c08e5c070000e411000009001c00636f6d70696c652f6355540900030d6ad2511aa8335275780b000104f50100000414000000c5576d53e34612fe8c7f45af70ddc10649b681ec2d5993781dd875158b293095da5b52419646f62c7abb9911c697cd7fcf3333926c0349ea2a499dabc0524ff733fdf274cf78fb853fe5993f0de4bcb54dfe5ffc31906fc7c3018db384678c8679a698543488529e71a944a07261b4089f615e2c059fcd15ed0c77a9d7e9ecbbbd4eb747d3251990aba5542ca59d691e06d23c7f374b039e78619eee1a94066a32e7920a91cf4490121e63c118c93c568b40b0235ae6258541468245da0b3e2d1523ae28c8223f1794e6118f970d1816ca2c6282d49c9162229594c7e6e5ddf935bd631913414217e534e1219df19065925180fdb544ce5984081a306d76aabdb9aabca1d31ce881e279b6478c635dd03d1312efb45f6f54a1eed15aba7602a503119417da7817de2f2909d4cadefbfda4ac628f8867669f795e20c4399011f48227094d199592c565b2d7e0c0827e184dde8faf273438ff483f0c2e2f07e7938fdfc042cd73acb27b66f1785a241cf0085404995a229e06e6c3c9e5f03dec066f4767a3c9478446a7a3c9f9c9d5159d8e2f69401783cbc968787d36b8a48bebcb8bf1d5894774c5b483ac41f99d12c4a694c870c4146822bdc6e823ea2fe16912d13cb867e041c8f83dfc0c280409ffb8bc0d5290e4d9ccc40d8b557ae1e828a62c577b24e1f09bb952c591ef2f160b6f96955e2e667e6291a47fecfd2dad7716a0d10c93518023ea75fdcf65b2f4eb8e420b8122dff19479a52cbc297805378a00ce335048926e1448da5df44d2942f653cc131d77bb47ecc1bed14ec4e2a04c148932f320d55dd8de2705d484a7a0d08ee56690ec9115a85ceb226d89c9b06005535cabc83ded558d976709aa90ad2b18ec034a83079e96a946c817a858ca50e225ed80c01fde2e315df61a90c36eefc3ae094b6f055e16a066555a594e532e4d93554400d1230e22e86e8097464961200422aa6c1b28c1542932502562da7a410bdd31f3a0284096088d1986b9883888012019c40ca9097775363b94dfe1ab0bdbb4d01964429896eee9b4e8bc3592f534b28790b18845101fa055511fa4b4d13c5c95ad917dfd54ed559dab2798ff0249c35270f4a79a0b1620527afdc4a15c8fa69f74d0861c98897759bec8748c7a221fd919821e00b5989dd8668214824996a9aa6bfff09368de9ad302a57a5481ba309fcb68c65aad320de41d753abd5eabc563fa444ebbeb50bf4f8e433f7ea335b3968664e13c2767952434e93461a963171f908c83fd56cc2dc60b7285057a0a8166404f63b06062eb79c5d53fa59120695100cc75c8030d9961cbbe46fb3bc0d33b3096dc82a408eb04fcac77dcde7ee9fd6233f15f5e38e406cfacfc7b74b17263abcca0092dad0483c8606e59f75f7aa1d36209c6de56b8d2d10a3a6911b9a561c0670c593af62376ef67250adf5b3d5befdadfea8d3bab4db555d9bfdd84b8b5f29995cfd6e5c81308570bbc0cb3362f0bf22ae736e0b27c9a47cbc7608dd442d9d735a09a054edb80d56c72f34a327bccaf2d5b3cd32414f3ccb43d4e4c518f3a190a5e28078a2ba29939824eb16b986977e8291819ca57f342dbeb6f7d35d19c37d3c61c340993faa0e1f7182033ce24d03402669aa80e6f3bc8728145cc350c4ad3b0184a5aecb564dcbf5dcc79386f36bb6d7d22f70131ca18a1d1972f041dbf94c25c0f6bad86c30f04c5e7282ce3dfe0b08de4318bbf362c0e1ed23e6675a7d3a9737fe0d08b475dbc5a71678a9cce6607198cf6cf07bf6810421daa2c4ba6942948612f6fcd44dd18d17aa99a09b507fb8febac4dfb879668e6b96d2a993038a3cc9cec53db7c7f45fb1da71948bda70309b9e85707a6c5b3a276cf382d5272636a43521f125c7938714cfd61b6f6b6de6021cafee6cdc9f8948ed7ecf4b57eed8a1f86fd9baaf2b330bca9ab7ed30ec3aaecd068ca0e95b58ad75a4f8aaee5ff53d55fe938433b612a5417835ee17ae58e7bbad73ccf3709309386dc246d99ad6e30408ef5e293c4b40c72a785f85be11cb71e7a7578b89907ecc845ffb65844b716cd1996e8876cd52d4b7d296e6b35878effd1b35ae69dbed00c371172ff438e5f8f1fe7f9a1a6e3b45b6dd84b5c0c1ce97b2f6f1a00df776ed7f238aa0fec67fcb1a86b25b644785c5e6c51e97fd224f241ead00f8b9267716eeb6b9453144a91ab34e943fbcfa8b6b6d7975d3c47a41966fe6948bce287977f073f59f21c20566359fdd7aa2d3d115cd125f7b48bd62437c3dfb59dade4beb32395dca187aa4b15e194c73a7e21e2d796c053d4d68d4d6e5a7dc77a4840aedab6e5dc49f5b04e0924628d2d1b1dd3d019813427d6aa702696d2066312b2d16144db1b0a3a278f6c10f1860978ae19698ad7b0b2299d158b1c8db2f2c616d0af956ce58ddbb8396cb6ff3ae3b620ecdf6a866ce854fc32a7a35179fdda48e09815569d30fe70313a1b4c46e3731a5dd1f978426f4f46e7ef68f8fe723c9e9c7c0f36d0e43d96aad501f4be3f199cd1d5c96402451c6fff9f4a9ba0dadf36e73658ef54f06b67b4ce4b237eacab1d7846d788b5ae1ec822d6d3caaf873a36b52754e7c9456068bc333fe7e9445f0a8ec8a857e7bf7e36a075b99e8cb2db3fb589c6edea0d56bbfd0a504b0304140000000800433ee2420dfbc7c056070000d61100000b001c00636f6d70696c652f63707055540900030d6ad2511aa8335275780b000104f50100000414000000c5576d53e34612fe8c7f45af70e5601749c640f696ac49bc0eecba8ac514984aed2d57419646f62c7abb9911c6c9e6bfe79919497e814beaea2e75ae024b3dddcff4cbd33de3ed17fe8467fe2490b3d636f9ffe38f817c371af46994253c6334c833c5a4a27e94f28c4b2502950ba345f80cf26221f874a66867b04bdd4ee7c0ed76f6bb34599001b95e48c552da99e46120cdf30fd334e08917e6e9ae4169a0c6332ea910f9540429e131168c91cc63350f043ba6455e5218642458a4bde0935231e28a822cf27341691ef178d18061a1cc222648cd1829265249796c5ede5fdcd07b9631112474594e121ed2390f59261905d85f4be48c4588a001d36667da9bebca1b3acb811e289e677bc438d6053d3021f14e07f54615ea1eada46b27503a104179a18d77e1fd8292402dedbd3f4eca32f6887866f699e505429c011941cf7992d0845129595c267b0d0e2ce8a7e1f8c3e8664cfd8b4ff453ffeaaa7f31fef41d2cd42cc72a7b60168fa745c2018f404590a905e269603e9e5e0d3ec0aeff6e783e1c7f426874361c5f9c5e5fd3d9e88afa74d9bf1a0f0737e7fd2bbabcb9ba1c5d9f7a44d74c3bc81a943f28416c4a890c474c8126d26b8c3ea1fe129e2611cd8207061e848c3fc0cf804290f0cfcbdb2005499e4d4ddcb058a6178e0e63ca72b547120ebf9d29551cfbfe7c3ef7a659e9e562ea271649fa27de5fd27ae7011acd30190538a6eebeffa54c167edd51682150e4079e32af94853701afe04611c079060a49d28d02497b1f7d538a90fd1cf344c7ddee127bb46fb413b138281345a2cc3c487517b60f480135e12928b463b919247b64052ad7ba485b62322c58c114d72a724f7b55e3e559822a64ab0a06fb90d2e091a765aa11f2392a96329478413b20f0c7770b4c975d13880607130b90b12aa62c272997a6adaad283da1147e935ffe1975152180181882adb064a30558a0ce48898b69ed35cf7c82c280ad023422b86612e220e2a004806314332c25d9dbf0ee5f7f8da876d5ae89c31214c1377752274a61ac96ae2d863c858c422880fd19ca80892d8681e2d0bd5c8be7daaf6bacece13ccbf83966129383a52cd040b1029bd79e250ae87d1cf3a6843074cc1fb2c9f673a463d838fedd400eb41266667b4991985609265aaead33ffd249aa9e67c40a9362a5017e64b194d59ab55a681bca74ea7db6db5784c9fc969ef3bd4eb91e3d03fbfd39a594b43b2709693b34c12da7292b0d4b18b8f48c6e1412be616e305b9c2023d8500fdd1c5182598d17a4271f5376924485a14007315f2504366d8b2a7d1fe0af0f41e8c25b72029c23a01bfea1db7b75f7abfd94cfcc20b87dce099957f0c2f976e6c951934a1a595601019cc2debfe4b2f745a2cc1a0db0a973a5a41272d22b7340cf882b14a277ec41efcac44e1bbcb67eb5dfb7bbd7167b9a9b62a7b77eb1077563eb5f2e9aa1c7902e16a819761bae665415ee5dc1a5c964ff268b109d6482d947d5d01aa59e0b40d58cd2637af24d34d7e6dd9e29926a19867a6ed71468a7ab8c950f04239505c12cdcc11748a5dc314bb474fc1c850be9a17da5e7febcb88e6bc9936e6684998d4470b7fc00099722681a61130d344755cdb41960b2c62ae61349a86c550d262af25e3dedd7cc6c359b3d95deb33b98f8851c6088dbe7e25e8f8a514e642586b351c7e24283e476119ff1b0edb483659fcad6171f098f68ef671c3ebd4b93f74e8c546172f57dca922a7b3de4106a3fdebe16f1a8450872acb9229650a52d8eb5a3351d746b45eaa6642edc1c1669db569efc812cd3cb74d251306679499933d6a9bef5774d0719a81d47d3a90908b5e75445a3c2b6a778dd3222537a63624f521c1958713c7d41f662b6fab0d16a2ec6fdf9e8ecee864c54e5fe4572ef561d8bbad2a3f7df5eab6aefa6d3b0cabb243a3293b54562a5e6b3d29ba96ff47557fade30ced84a9505d0c7a850b953beaea5ef33cdf24c04c1a7293b465b6bac50039d18b4f12d332c89d16e26f8533dc73e8f5d1d17a1eb02317bdbb621edd59346750a21fb265b72cf435b8add51c3af9a66bb5cc3b7da529ee1ee4fe8b1cbf1e3fcef3434dc769b75ab397b81838d2f75ede3600beefdcade471581fd8cff86351574a6c89b0595e6c51e97fd624f241ead00f8b9267716eeb6b9453144a91ab34e943fbcfa8b6b657975d3c47a41966fe6948bce2a7967f0f3f59f21c20566359fdd7aa2d3d115cb14feed93e5a93dc0c7f3776b692fbde8e5472071eaa2e5584531eebf84d88df57024f515b3736b969f51deb2101b96adb9673c7d5c32a25908815b6ac754c436704d29c58cbc299584a1b8c49c85a87116daf29e89c6cd820e23513f05c33d214af6165533a2b16391a65e98d2da05f2bd9ca1bb77173586fff55c66d41d8bbd30c59d3a9f8654e47a3f2e68d91c0312bac3a61f4f17278de1f0f471734bca68bd198de9d0e2fded3e0c3d568343efd116ca0f1072c55ab7de8fd78da3fa7ebd3f1188a38defe3f953641b5bf6fce6db0dea9e057ce689d9746bca9ab1d7846d788b5ae1ec822d6d3caaf873a36b52754e7c9456060bc333fe0e9545f0a8ec9a857e7bf7e36a075b99e8cb2bbff6a138dbbaf3758eef63b504b030414000000080002732c43b35fad82ec070000451300000c001c00636f6d70696c652f6a617661555409000364b231521aa8335275780b000104f50100000414000000b5586d53db4812fe8c7e4547b8362487256348722131bb8e171257114c81a9addc72b596a5913d416f3b9230decbfdf77b7a46920d26d96cdd9d3f18a9a7e7997e79ba7bccf613772a1377eae5736b9bdcfff14743be1b0dfa344a2299081aa44921f282fa412c139917ca2b52a5b5089f419a2d959ccd0bda193ca36ea7b3dfee76f6ba345d9206b95ce6858869679afa5eae9f7f9ac59e8c1c3f8d9f6994066a3c9739652a9d292f263c864a08cad3b058784a1cd2322dc9f7125222602be4b42c04c982bc24705345711ac870d98061a14c02a1a8980b2a848a734a43fdf2feec8ade8b44282fa2f3721a499f4ea52f925c9087f35992cf45000f1a30de76c2d65c56d6d0490a74af9069b24b42625dd1ad5039de69bf3ea842dda5b570ed78053ba228cd78f33358bfa4c82b56fb9d6f0765e57b4032d1e7ccd30c2ece810ca717328a682aa8cc455846bb0d0e76d02fc3f187d1d598fa679fe897fec545ff6cfce90d7614f314abe256183c196791043c1c555e522ce14f03f3f1f862f001fbfaef86a7c3f127b84627c3f1d9f1e5259d8c2ea84fe7fd8bf1707075dabfa0f3ab8bf3d1e5b1437429d840d1a07c2305a14e25221c880234c99d66d327e43f87a5514073ef568007be90b7b0d3231f24fcf3f436485e942633ed3776acc20b4387212569b14b390c7e3b2f8aecd075178b85334b4a275533373248b97be4fc5f4aefd443a16926230187d4dd733f97d1d2ad2b0a25048afc2463e19479e64cc12b989179305e80423971a140d2da43dd94ca17bf853262bf5b5d1277e68d7602117a6554902a13e7b3a7b80a5bfb540035923128b463b8e945bb640445caba085ba423ac44260ac92af92e5b55e3a549842c24eb0a1afb8062ef4ec665cc08e902198b0552bca41d10f8e3bb25bacb33ed0883838919c85825332fa7b1cc755955a907b50389d433ff6197562ad0023c15547b1b28258a522520472078f782165c23732fcb408f00a5e8fba90a24a800a0dc0b0542e43fe3f87528bdc19f3dec8d338e99504a17719703c1916a24eb811377be108108203e407122230862a3f96295a846f67253ed551d9d0dccbf83967ea9242ab2982be1c1537abd6150cacde837765ad3015df026491709fbc83df8d0740db01e6412a647eb9e9129918ba4a8eaf44f3f113355cf07a4ea4106eac47c2e8399b0ac32f6f21bea74ba5d4b86f42bd9ad3d9b7a3db26dfae71b564c2c4614fe3c257b152354e53412b16d16ef108b837d2b945682f51e6318b427d4560672130c758072464f41b3e656258ba7b996207a8107f475f003069701b54b1da8cfe83e74e406e2d64d4ac4a7bb7a3607b77e64273acdb15bbcabec4dee434c8c7c66e4b375394c445e6a8193a009a565468e252234abfb70493a4d83e543b0466aa0cceb1a103b6422aec1eaa8b7d34a327b98872d1337cd250a65a2ab03a344d53d20f795cc0a1b8aab8458f10d4a92da19e5caaf0ffc17a7697bfbb9f36f73c61f32c3c1de232bff189eaf195026d084162b6143a031f540ff066eb37b4b936382ab91e027d230e44009413261fd8a0aac800abc315a7e562df0f1909a00b72fc844528bfd393a35cb5e753a5aa0fb0e2acb04095def063588e8e912a9fa0b0792fff2e5856b4477273d8a2291f32892b76838332972a031027aa0aac6bb697ca9c222fa205aa92e703431163b561ef6268bb9f4e7cd6113eb576adf216a798820d1972f041db7cc95be40d65a4d1ddd11141f2ba33cfc4a1d194f1e56d24bcd0aee4abdd6414d087e45426705d99df55a8d0424856e613dd25af437daefd82611a4db6b6f8f1ff4d37ec750cebb8b7b2ff69a8e7260d313c36dcfbc3d3c486f68e91aaff77437bb10c621cfc85e351a6b1b6a71ababf7fb48446f922d82896502342891826495a025dfd45aac66d3d10f5d1c2e13433bad6f98f585103a30fca9f394dae1dea436ab56deb4ae81f98807630958f1f6edf1e8848eea59250b877f17acfd46f8ecdd7a7e4d0dfd52f3e2baa55f2b6e18bd861efa758d1b95f223fca857fe0a475eb1fd88ebca2eb56e956a6c52eb16a907f6a8af58a3feba2de22e4b554183d3fee5e5797ffca1e71c5eb79a37e47cd5ddf4c031295a1d5f07a15ab12a73ae31268ec871dc557a702fe1aae743b9c592436d4d08fbb9c3103698819844424f299eb16f2848d74fb86e41f83d2704b88d592a06bd78bd55d1d83211f26f43b12ea68679f4dcd281e9586056d5e65ebd78719f6186fa9ae43078863b1fb57f27dbade799fdf894e4a89bfab9b73fc785ccce5de7f97503e0baf6c4a8af911c85b94970e4c636d5c60c0ac9c5d5c177fdac9449981a1e69e5187c40c5157cb5f0cd9756b5b6d797db534e0a0f7bfdc59078c56f57f7063735113d0688d530afbe59d5b2b867b6d51e164d4f6b8f5ba6c9b54fba2f5e62a4ebef342f02dc993ad4c62f6cfc5a5578baaa464cfbbd99d16007a403b024c4cf6b8c9a76a0ffeac7b8795ccf3f62b2460d13b39a137501c1a7e69ab34a8e76ab347ee9d8dcbbf7106ddf53e0f03cd803e7ef6d01c999443a8f0d919a2c1ab14a519a2b6b4c2edd5ac95e8d7f14de3d67eeb16a0bc2de84c9724fc770a89afdacf2fab59634a3be6ae1a38fe7c3d3fe78383aa3e1259d8dc6f4ee7878f69e061f2e46a3f1f1cf20068d3f60a95aed43efe7e3fe295d1e8fc750b4adedad072d60ebfb48b099f0c7685193e07b92affd6cfdd8dcff50137605b97659e25035e287ba7ce823ba5accbadc5554c83dd1ad07170e3553b8b371a11c68ebf4ff4be8982f9787a4d5ab7b243f6bd03a831bad6cf25f1dc2b87ba6c9d7a7fd07504b0304140000000800433ee24202c9277e58070000de1100000b001c00636f6d70696c652f70617355540900030d6ad2511aa8335275780b000104f50100000414000000c5576d53e34612fe8c7f45af705d600f49c640364bd6245ec7ecba8ac51498ca6d965490a5913d416f3733c238d9fcf73c3323c93690a4aeee52e72ab0d4d3fd4cbf3cdd33de7ee14f79e64f03396f6d93ff3ffe18c8b7e3419fc659c23346833c534c2aea4729cfb8542250b9305a84cf202f9682cfe68a7606bbd4ed740edc6e67bf4bd3251990aba5542ca59d691e06d23c7f3b4b039e78619eee1a94066a32e7920a91cf4490121e63c118c93c568b40b0635ae6258541468245da0b3e2d1523ae28c8223f1794e6118f970d1816ca2c6282d49c9162229594c7e6e5ddf935bd631913414217e534e1219df19065925180fdb544ce5984081a306d76aabdb9aabca1d31ce881e279b6478c635dd03d1312ef74506f54a1eed15aba7602a503119417da7817de2f2909d4cadefbf3a4ac628f8867669f795e20c4399011f48227094d199592c565b2d7e0c082be1f4dde8faf27d43fff48dff72f2ffbe7938f5fc342cd73acb27b66f1785a241cf0085404995a229e06e6c3f072f01e76fdb7a3b3d1e42342a3d3d1e47c787545a7e34bead345ff72321a5c9ff52fe9e2faf2627c35f488ae98769035287f5282d89412198e98024da4d7187d44fd253c4d229a07f70c3c0819bf879f018520e15f97b7410a923c9b99b861b14a2f1c1dc594e56a8f241c7e3357aa38f6fdc562e1cdb2d2cbc5cc4f2c92f44fbcbfa5f5ce02349a61320a704cddaffca09cf97543a183c0906f79cabc5216de14b482174500df19182449f70924ed7db44d2942f653cc131d76bb4becc1bed14ec4e2a04c148932f320d54dd83e2005d484a760d08ea56690ec9115a85ceb226b8949b06005535cabc83ded558d9767098a90ad2b18ec434a83079e96a946c8172858ca50e125ed80bf1fde2e315cf61a90a3fdee875d1396de0ab42cc0ccaab2b29ca65c9a1eab78009e471c3cd0cd002f8d92c23c084454d9365082a95264604ac4b4f58216ba61e64151802b11fa320c731171f002403288195213eeea6c7628bfc3d73e6cd342679009613abaabd3a2f3d648d6d3c81e42c62216417c884e457d90d246f36855b646f6e553b55775ae9e607e058e86a5e0684f35172c40a4f4fa8943b99e4c3fe9a00d393012efb27c91e918f5403eb623042d006a313bb0cd002904932c5355d3fee527d1b43587054af5a80275617e2ea3196bb5ca349077d4e974bbad168fe91339ed7d877a3d721cfaf16bad99b534240be73939ab24a147a7094b1dbbf880641c1eb4626e315e902b2cd0530834035a1a7305035b8f2baebe904682a4450130d7210f3564862d7b1aedef004fefc058720b9222ac13f0abde717bfba5f79bcdc42fbc70c80d9e59f96174b17263abcca0092dad0483c8606e59f75f7aa1d36209a6de56b8d2d10a3a6911b9a561c0cf98b174e247ecdecf4a14bebb7ab6deb5bfd11b77569b6aabb277bb09716be5332b9fadcb912710ae167819466d5e16e455ce6dc065f9348f968fc11aa985b2af6b40350b9cb601abd9e4e69564f6985f5bb678a64928e699697b1c98a21e753214bc500e1457443373049d62d730d3eed053303294afe685b6d7dffa66a2396fa68d39671226f539c3ef3140669c49a06904cc34519ddd7690e5028b986b1894a6613194b4d86bc9b877bb98f370de6c76dbfa44ee0362943142a3cf9f093a7e2985b91dd65a0d871f088acf5158c67fc0611bc963167f69581c3ca43dccea4ea753e7fed0a1178fba78b5e2ce14399dcd0e3218ed5f0f7fd320843a5459964c295390c2dedd9a89ba31a2f55235136a0f0e1ed7599bf68e2cd1cc73db54326170469939d9a3b6f9fe271d749c6620759f0e24e4a2571d9816cf8ada5de3b448c98da90d497d4870e5e1c431f587d9dadb7a838528fb9b37c3f1299dacd9e95bfdda0d3f0c7b3755e5e322bca9ab7ed30ec3aaecd068ca0e95b58ad75a4f8aaee5ff51d55fe938433b612a54f75f0a7f57e48ebbe835cff34dfc66d0b4cc2e37981d27848527396919d04e0ba1b7c239ee3bf4eae8683305d88c8bde6db1886e2d9a3328d10ad9aa5196fa3adcd66a0e9dfca36bb5cc3b7da6192e21e4fe9b1cbf9e3ccef3f34c8768b7dab097b81338d2f75ede3400beefdcaea570549fd5cff86351d7aa6b39f0b8b2d8a2d2ffa4f9e383cfa11f1625cfe2dc96d628a7a81152ad34df43fbcfa8b6b6d7975d3c47a4c965fe6948bce227977f073f59f21c20566359fdd7aa2d3d0c5cb14feee93eba92dc0c7fd776ac92fbce4e5372071e0a2e5584031eebf86d88df59024f515bf734b969f51debf900b96adb6e7327d5c33a25908835b66c344bc36404d21c56abc299584a1b8c49c84673116d6f28e89c3cb241c41b26a0b866a4295ec3caa674562c72f4c8ca1b5b40bf56b295376ee3d2b0d9f9eb8cdb82b077ab19b2a153f1cb1c8c46e5f56b238163565875c2f8c3c5e8ac3f198dcf697445e7e309bd1d8ecedfd1e0fde5783c197e0736d0e43d96aad53ef4be1bf6cfe86a3899401127dbffa7d226a8f637cd910dd63b15fcdaf1acf3d2881feb6a079ed13562adab67b188f5a0f2eb798e4dede1d47972071818efcc0f791aeafbc03119f5eae8d7cf06b42ed7935176fb5f6da271f7f506abdd7e07504b03040a0000000000433ee2420000000000000000000000000c001c006465736372697074696f6e2f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242ff36f663470000005000000014001c006465736372697074696f6e2f626974732e74787455540900030d6ad2511aa8335275780b000104f501000004140000001dcab10dc0200c05d13e53fc2cc00e064cb00436326eb2ff224191ae7abae8b2718ace683218957771c9a2cf6fcb2d0f9e37245048911984551bcc41fac2cee468e69322a5747d504b0304140000000800433ee242578f11bd340000003d00000018001c006465736372697074696f6e2f70726f626c656d2e696e666f55540900030d6ad2511aa8335275780b000104f501000004140000004b4a2c4ecd4bcc4db54dca2c29e64a2bcdc901f342325215028af29372527315f2d314c09229a9c5c969993910a57a2515255c00504b03040a0000000000433ee24200000000000000000000000006001c00696e7075742f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee2422d30c7173f000000150800000a001c00696e7075742f6269747355540900030d6ad2511aa8335275780b000104f50100000414000000edc9d10900310803d0ff4c9111a22d58f75fececdd1407e1a111132c6c06434c71093929b1bbb1a86b5eb79770d75cb56ac7c9fdd5666666f65f88770e8407504b03040a0000000000433ee24200000000000000000000000007001c006c696d6974732f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee24271deea4ec20100007703000008001c006c696d6974732f6355540900030d6ad2511aa8335275780b000104f501000004140000006d934d6edc300c85f7730a16d9b4c0209909da437451f40cb445db6c24d1d04f3c93d39794c6cda4edce90c48f8fefd10f9f9e068e4f03e6e5f00065e10c74a1b1161c3c415ed07b905ad65af49220d63050029920d328d1d957e1409e0317e0d81e4d9c7201cf918e30496a6733bf528435896203a0566e5c16f018e78a33018ea324c77186225dc6c49e22063ad0b8087c557556f55144a2950a179698ad2ed5d8b0371dbde73ff26eca4d5f679f4f77f080170e552506a9b158934041d215566df9de6f8769b3d459f0598f7e0cd742f94be77e3b3fff076c7341e637fa6397d46456ec8c977bc6f9f4bc8fdec348546a8af046496c648e8e472c6a8ac084ec6bd2a12e3aeca9c5a94d1c21a89d65c1f26eacdaa4ac816e619383e1da9e632df2abba993e866926fe75ef648b5ed065c03dd7236401cca0ed6f7c936e8ba44a1d8daaa541f4b9b9d9a03d9a7d4d028e8bf9b02d94c838aa57538dba188ff07d02b13b8ca5c53df1a5f526479316b93be0d168b103540345a9f362355b62350b218f89575bd217eaae34b2ada4c9709459b1f08abed223fcd4b3b471d67db6672346182a7ba7b15c419743f3945521a545e16cbe3961e89ef7bf27dfe2e8c4c36f504b0304140000000800433ee24271deea4ec2010000770300000a001c006c696d6974732f63707055540900030d6ad2511aa8335275780b000104f501000004140000006d934d6edc300c85f7730a16d9b4c0209909da437451f40cb445db6c24d1d04f3c93d39794c6cda4edce90c48f8fefd10f9f9e068e4f03e6e5f00065e10c74a1b1161c3c415ed07b905ad65af49220d63050029920d328d1d957e1409e0317e0d81e4d9c7201cf918e30496a6733bf528435896203a0566e5c16f018e78a33018ea324c77186225dc6c49e22063ad0b8087c557556f55144a2950a179698ad2ed5d8b0371dbde73ff26eca4d5f679f4f77f080170e552506a9b158934041d215566df9de6f8769b3d459f0598f7e0cd742f94be77e3b3fff076c7341e637fa6397d46456ec8c977bc6f9f4bc8fdec348546a8af046496c648e8e472c6a8ac084ec6bd2a12e3aeca9c5a94d1c21a89d65c1f26eacdaa4ac816e619383e1da9e632df2abba993e866926fe75ef648b5ed065c03dd7236401cca0ed6f7c936e8ba44a1d8daaa541f4b9b9d9a03d9a7d4d028e8bf9b02d94c838aa57538dba188ff07d02b13b8ca5c53df1a5f526479316b93be0d168b103540345a9f362355b62350b218f89575bd217eaae34b2ada4c9709459b1f08abed223fcd4b3b471d67db6672346182a7ba7b15c419743f3945521a545e16cbe3961e89ef7bf27dfe2e8c4c36f504b0304140000000800433ee24271deea4ec2010000770300000b001c006c696d6974732f6a61766155540900030d6ad2511aa8335275780b000104f501000004140000006d934d6edc300c85f7730a16d9b4c0209909da437451f40cb445db6c24d1d04f3c93d39794c6cda4edce90c48f8fefd10f9f9e068e4f03e6e5f00065e10c74a1b1161c3c415ed07b905ad65af49220d63050029920d328d1d957e1409e0317e0d81e4d9c7201cf918e30496a6733bf528435896203a0566e5c16f018e78a33018ea324c77186225dc6c49e22063ad0b8087c557556f55144a2950a179698ad2ed5d8b0371dbde73ff26eca4d5f679f4f77f080170e552506a9b158934041d215566df9de6f8769b3d459f0598f7e0cd742f94be77e3b3fff076c7341e637fa6397d46456ec8c977bc6f9f4bc8fdec348546a8af046496c648e8e472c6a8ac084ec6bd2a12e3aeca9c5a94d1c21a89d65c1f26eacdaa4ac816e619383e1da9e632df2abba993e866926fe75ef648b5ed065c03dd7236401cca0ed6f7c936e8ba44a1d8daaa541f4b9b9d9a03d9a7d4d028e8bf9b02d94c838aa57538dba188ff07d02b13b8ca5c53df1a5f526479316b93be0d168b103540345a9f362355b62350b218f89575bd217eaae34b2ada4c9709459b1f08abed223fcd4b3b471d67db6672346182a7ba7b15c419743f3945521a545e16cbe3961e89ef7bf27dfe2e8c4c36f504b03040a0000000000433ee24200000000000000000000000007001c006f75747075742f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242578d8e84370000007f0000000b001c006f75747075742f6269747355540900030d6ad2511aa8335275780b000104f50100000414000000f3cc2b2e49cc4bce4c5430e432e6e2f284738db88c0c90f9c65c4646c87c1354e5a668cacdb88c0d0d0c5084ccb90c91b9165c265c5c00504b03040a0000000000433ee24200000000000000000000000004001c0072756e2f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee2424a41584c17060000ef0d000005001c0072756e2f6355540900030d6ad2511aa8335275780b000104f50100000414000000b5576b4f1b4714fd5cff8a1bc76a4165fd04d2923a8963850495003246118a2a58efce7a27ecce6c6766316e93ffde33b30fdb4012a96a2da4dd79dc735fe7debb3c7dd29971d199f93a6e3ca5ce7ffc7390af4fc7233a1509178cc65218a60d8dc2940bae8df28d54ee16e13796d952f1796c686bbc4dfd6e77e0f5bbbd3ecd96e440ce97dab094b66632f0b57b7f354f7d9eb403996e3b941a6a1a734d999273e5a784d74831465a4666e12b76404b9953e00b522cb456f0596e187143be083b52512a431e2d6b301ce422648a4cccc830956a92915bbc3db9a0b74c30e5277496cf121ed0310f98d08c7ce8b73b3a66213ca8c1acd8a1b5e6bcb4860e25d07dc3a5d821c671aee896298d350d2a4525ea0ead856bcb37d6114532b3c2dbb07e49896f56f2ed6f0765e57b485c383db1cce0620c6438bde049423346b966519eecd43890a00f47d377a717531a9d5cd287d164323a995e3e878489254ed92d2bf0789a251cf07054f9c22ce14f0df3fecd64fc0e72a3d747c747d34bb8468747d39337e7e774783aa1119d8d26d3a3f1c5f16842671793b3d3f3376da273660d6435ca375210b95422c22133a0896ed74297c8bf86a54948b17fcbc08380f15bd8e95300127e3fbd35929f4831777e4362155e187a1491906687340cfe2d36263be874168b457b2ef2b654f34e5220e9ce8bf6ff527ac73e0acd31190938a07ebfe3e7f34e5550a82030e4154f593bd7597b065ac18acc87ed0c0cd264eb043bad1eb13b7615f1c4fadcea8328596eeaf5800c20129e822e5bc5c34852b94060121743c53266b825a8deb18a4316f97962488a047116eb176c0db77649e4e98ca92b195dadc95e19796561b70ab2fbc98e2be0829da14bf58c19cb7d930b8e94b85aad6c73c87b94fa773ccd536b9b5c402a65a0c792b640fef7af97e84ceedefe837ba0349c26cdff62eef2efd5655cb77acaf392353a9fa55cbbfa2d39062b430e8ed9424378dc25835ee3ab0abb8652cce44a808521b3d20b5ad8628cfd2c030f43d47c1048153a0725693f62c84eb06d33d5257983470fb26986f41053ca758bbecd870d45bdb39e3576173016b210dbbb482e2288e0d637f75694a8f7f61f5e7b5685f201e62fe07f902b8ed237b1623e3ca55f1f18246dd7bbb24e3be2a1ddde08b910d647dbec0f8af684f2026d59310c5c73ca14d34c98b2217cf797d892708308a9ba97812a319ff270ce1a8d3cf5f50d75bbfd7e8387e4e5ce8e4fe822f4a213b2db8ec8a1bebf7a6ff0883e52eb250d87c8c41fcf2d9668fc60a5f2e1f526c475b13f2ff6e7ebfb416cddae36da02cd44e619b51b2c41d3d984137226c3e57db07ab7802a966b40112f4c6db61c58d31adc6c9227cb9d79b553fbc080444d972a8ab870e443d1a9aad275a078669ab87887dcef0eac0ac766e4ab384341dd20b31072812f596be5edd3ce5e1b79c779d74913a66d27e5b7a0f19c330d348b80ca52e5742aca492a1ca2ba50a58e36280dbbdd6ee86878bd887910d7caae1b1fc9bb838f3a826bf4f933e14e27d7ca7dff54b71a556c7a1b71e96fac06f72364a9550469552c9803b384a5cde270151887ffa430a5f718041a2ec6061a1a3e0ad0ed941dd476a31cd0a82565198f006cdb89c9cd4fc57911491f4ad775eeaeeb54852b8fe8ecff3b9da8e7f0bec6bd4d2f11efc714ea684de3f7ddd8779cb22d63d81a5439b24ba4646ea8d95df72a61d831aebf0cc9dda29f69d06d163544aef70d7bf6c5bd0dba0e5c80ca1adb25f86e939edc4bf2eae4a14ea242beb58b05e0f08721920ef77af88ced56a07b5f05dd7b1cd461b4fedefb6241d680a361afdbdfad60f7bf0abbff55d808b0fb5f6acc469015e4d006f5dddde86cee085c85836da467e3ac613f04ea93cf34c7c8268fff494db457c3034c503b14c40d0b9b95b92f0b73376c2a68319a49656c7b9931b426740b144349c547e02a723c2b1c003b87d7d922bc6e1460e3156b5dab585aa896bd469e4741aca474ba30a6b969d28b1ffb0d4b4befb0d7252f6ad918a1645a2eafe4891e7993527adc26efa2689fe4bd2dba265a034287510261fc8783ff1614de420b939297964f53d0d19bb60a7e7abc8c77bb53c6b0810f8061eb65394eb07009ecad8d147ba15b1799670759a17715cdc037d5aebde8c264b11aff00504b0304140000000800433ee2424a41584c17060000ef0d000007001c0072756e2f63707055540900030d6ad2511aa8335275780b000104f50100000414000000b5576b4f1b4714fd5cff8a1bc76a4165fd04d2923a8963850495003246118a2a58efce7a27ecce6c6766316e93ffde33b30fdb4012a96a2da4dd79dc735fe7debb3c7dd29971d199f93a6e3ca5ce7ffc7390af4fc7233a1509178cc65218a60d8dc2940bae8df28d54ee16e13796d952f1796c686bbc4dfd6e77e0f5bbbd3ecd96e440ce97dab094b66632f0b57b7f354f7d9eb403996e3b941a6a1a734d999273e5a784d74831465a4666e12b76404b9953e00b522cb456f0596e187143be083b52512a431e2d6b301ce422648a4cccc830956a92915bbc3db9a0b74c30e5277496cf121ed0310f98d08c7ce8b73b3a66213ca8c1acd8a1b5e6bcb4860e25d07dc3a5d821c671aee896298d350d2a4525ea0ead856bcb37d6114532b3c2dbb07e49896f56f2ed6f0765e57b485c383db1cce0620c6438bde049423346b966519eecd43890a00f47d377a717531a9d5cd287d164323a995e3e878489254ed92d2bf0789a251cf07054f9c22ce14f0df3fecd64fc0e72a3d747c747d34bb8468747d39337e7e774783aa1119d8d26d3a3f1c5f16842671793b3d3f3376da273660d6435ca375210b95422c22133a0896ed74297c8bf86a54948b17fcbc08380f15bd8e95300127e3fbd35929f4831777e4362155e187a1491906687340cfe2d36263be874168b457b2ef2b654f34e5220e9ce8bf6ff527ac73e0acd31190938a07ebf');
SELECT pg_catalog.lowrite(0, '\xe3e7f34e5550a82030e4154f593bd7597b065ac18acc87ed0c0cd264eb043bad1eb13b7615f1c4fadcea8328596eeaf5800c20129e822e5bc5c34852b94060121743c53266b825a8deb18a4316f97962488a047116eb176c0db77649e4e98ca92b195dadc95e19796561b70ab2fbc98e2be0829da14bf58c19cb7d930b8e94b85aad6c73c87b94fa773ccd536b9b5c402a65a0c792b640fef7af97e84ceedefe837ba0349c26cdff62eef2efd5655cb77acaf392353a9fa55cbbfa2d39062b430e8ed9424378dc25835ee3ab0abb8652cce44a808521b3d20b5ad8628cfd2c030f43d47c1048153a0725693f62c84eb06d33d5257983470fb26986f41053ca758bbecd870d45bdb39e3576173016b210dbbb482e2288e0d637f75694a8f7f61f5e7b5685f201e62fe07f902b8ed237b1623e3ca55f1f18246dd7bbb24e3be2a1ddde08b910d647dbec0f8af684f2026d59310c5c73ca14d34c98b2217cf797d892708308a9ba97812a319ff270ce1a8d3cf5f50d75bbfd7e8387e4e5ce8e4fe822f4a213b2db8ec8a1bebf7a6ff0883e52eb250d87c8c41fcf2d9668fc60a5f2e1f526c475b13f2ff6e7ebfb416cddae36da02cd44e619b51b2c41d3d984137226c3e57db07ab7802a966b40112f4c6db61c58d31adc6c9227cb9d79b553fbc080444d972a8ab870e443d1a9aad275a078669ab87887dcef0eac0ac766e4ab384341dd20b31072812f596be5edd3ce5e1b79c779d74913a66d27e5b7a0f19c330d348b80ca52e5742aca492a1ca2ba50a58e36280dbbdd6ee86878bd887910d7caae1b1fc9bb838f3a826bf4f933e14e27d7ca7dff54b71a556c7a1b71e96fac06f72364a9550469552c9803b384a5cde270151887ffa430a5f718041a2ec6061a1a3e0ad0ed941dd476a31cd0a82565198f006cdb89c9cd4fc57911491f4ad775eeaeeb54852b8fe8ecff3b9da8e7f0bec6bd4d2f11efc714ea684de3f7ddd8779cb22d63d81a5439b24ba4646ea8d95df72a61d831aebf0cc9dda29f69d06d163544aef70d7bf6c5bd0dba0e5c80ca1adb25f86e939edc4bf2eae4a14ea242beb58b05e0f08721920ef77af88ced56a07b5f05dd7b1cd461b4fedefb6241d680a361afdbdfad60f7bf0abbff55d808b0fb5f6acc469015e4d006f5dddde86cee085c85836da467e3ac613f04ea93cf34c7c8268fff494db457c3034c503b14c40d0b9b95b92f0b73376c2a68319a49656c7b9931b426740b144349c547e02a723c2b1c003b87d7d922bc6e1460e3156b5dab585aa896bd469e4741aca474ba30a6b969d28b1ffb0d4b4befb0d7252f6ad918a1645a2eafe4891e7993527adc26efa2689fe4bd2dba265a034287510261fc8783ff1614de420b939297964f53d0d19bb60a7e7abc8c77bb53c6b0810f8061eb65394eb07009ecad8d147ba15b1799670759a17715cdc037d5aebde8c264b11aff00504b03041400000008006c0b2e43f347839861080000fa14000008001c0072756e2f6a61766155540900035c9f3352b1a8335275780b000104f50100000414000000cd577f73dbb811fddbfa141b59d373ae9628cb3fd2f8a2dc298a1c6bceb13292dc6be6d2b12812941893044b82967549be7bdf022445d94ed276da99ca1e8900160f8bddf716e0ee136bee47d6dc4e97b55db2fecb1f0df96ad4efd1280afc48505f464aa48a7a6ee8477eaa125bc9445b113e7d19af137fb154b4d77f4a9d76fbb0d9691f7468be260d3259a74a84b437978e9deae75f16a1ed072d47864f354a09355dfa29c5895c24764878f4122128959e5ad98938a5b5ccc8b1234a84cb5ef8f34c09f215d9916bc98442e9fadeba04c34016b92221b514a44412a6243ddd787379456f4424123ba077d93cf01dbaf01d11a5826caccf3de952b8d84109c6d3ced89b49ee0d9d49a0dbca97d13e091fe309dd8a24459b0e8b8572d47daa846bcf56bc918464cc939fc2fb3505b6dacc6f7d3b289bbdbbe4477a9da58cb1c52590b1e9951f04341794a5c2cb82fd120733e8b7e1f47c7435a5dee57bfaad371ef72ea7ef7fc20cb5941815b7c2e0f9611cf880c746133b526beca784793b18f7cf31aff76a78319cbec7d6e86c38bd1c4c2674361a538fdef5c6d361ffeaa237a67757e377a3c9a0453411eca02851be91024fa712117685024dd25639e93df29fc2d3c0a5a57d2bc00347f8b7f0d3260724fc7e7a4b243b90d142ef1b3336e185a3438f22a9f62985c32f964ac5a796b55aad5a8b286bc96461050629b55eb6fe27d2bbb02134cd6424e0940e8eac54c4ca2a1405098122bff8a1686569dc9a83577023b6e1bc00855262a1a0a77140d05874ed0498c0ed0eb81267eadaf3030e43e3901440023f0463f6cc8f929464116213e830262216ca678ea6fbbcb42b3c3b0b14c92840a8a3aa01cbb871445116ce45722dbdebcadc6b25af1976cff0dd0ef6b5860d415d9dedb9504c7f95453eb2a2e55af8a6918fb1973b3fcc42f64dae302b1460c89af6c0ffb7afd6284edaeee4811d588d4d53eaff21b4f1af8531cc799d7c3c274e9acd433fd512ce69062f5d1f3463ad213cda48a1dcd849815d42254265490422ba8267af68c57a5cda710c2aba90bde3c8c4d51b9494da9e1077c279cab96a93bcc1cf01e68631d243224974c1e8703e3814654f356be2ce11c2152eba8f905c4410c12d2d8f37a428fb4e1e9a3d2b42f900f32f908093253ed4af9689b0b1537afec021c985ef9a37ada9878a7b13c955c47be47a7f6a2a141406e20a731ee8fa1427221591ca6bc2773f01ab429f4548d5bd0c1489f998b90b51ab65a19dde50bbdde9d47c979a99f6e3230a09bdb45c716b451996ef6c9e6bbe47bf53e367ea769189bfffc458516d876765ddd936c4ccf42f4cffa2daef2c79db45472b423d91594cad9a085077b6e1223997eefa3e58d96ba04cb302e4f9c6d57a4383d5d9e17a9d9a32ef59143de51e0490a8ae53459e1f69f2417449a1f4d449fc58d5617887dc1f1df2129acdc8971983a06e90594cd281cf59cbf3f9978f5f8ebce6bc2ea68148b998fab7a0f1c2175c791801ca4af203cac849261884baa0524d1b4883bb5bb5d4ebce564bdf59968bcd6abf53f30e7b4c3d6c8d3e7f26d858599ae82b5061552b6273b01597ce56ebf07e84985a26481bb1e028980722ac9bc14d60347e33e1c0b5b062052309a9e9e9fe8f7682b6139746456765be76f19b93b5c5fd994f8ab5d159996ebc1f8cc7a3f129716d2f6d708aa1b8e28e424d8a25aadabca82ce442d6a0822937fa0ac3bbd57b3de0052384a23bc3ed52f093f66756c4f71377edeefed8fa6282a90f18c4d77e64ac7fd19b4c1e8b36ae1e97c3cb37a7289aa1bc655f5a1ac7ec40dc291cb1daab0de7bfb26ec9f5477cce4167df40b9e7e1e328da68b69d094dad871bc331bb89fb1e420d2571477e4f433d4db8ea41044ff9e2e4ab1fcc38eaab6b234155d61d5757bc23f0ffb10553afb26215d208f43ee889d63897f06ee3b0880a3791c285a27abbbaab40a047e97adf256d457fa6c376ddd434d26751f7801ff4d3615b834760608aee1cfca84e4fee896e33f2704d2233bf718406e0f08f433dec1e1fe0cda25d801e7f15f4f871508dd1f874fc85412ac05ef7a0dd392a604fbe0a7bf255580fb0275f4acc5a29685fb57027d0b5b17adc6881833ca9423d6e6f0dd51c10a33b8b57eeac66d2dbdf104657cd3513aac166757af9a78eb1d26dfa4c0bdcbaa8f90faa5bc531547ffc7063cfcd525bf3f93a564fadd68f1f4a00cb82f2b5394af78b1783d119bdccf7c6af9e95d75007738d635caf3db250471dcb89333ff2a4a9dbda380451153515d759c77c69d3da6e75b88967973832fa8b21d1c4dba37583fb8b081e03c4a897e6df6c5afb68dfdac569c2cfc549f2a1c1adfc30d146e571c2ad8ae68ce523b2cb07fe1de53d637688bb58268a744579d79b9e775ba72d2bafd8a78db2bbc6b26e260d2d05eccd68af396d183136fd9c3ccdb3cef1096e14fa5ba2133722f4e25d1defbd099eaecc5d819a6fcc15819a3cabdf4286584e7876f5af7e0c378fcd62ebcdbf85778d4fac9e2fbf72232d1bf50697c87a0d1cbfb583ee87c6cf868d1f1aa6a7244a2e8232ac48647925db4456873f33c9d484d85206d1ee960173e2de1c647c6b0aa20dba1af22ef14245cf8e8f37d435dd8944ba36de18025b85112e469543bf22e72a1f76d0d99db13cb66c708d83033b79f540e783cb597e3b1b68b2f02b87399b9b704b383764674af28d1687639d8d35879e6bd4bcfa165cec8f5e0fe87234a557039ca9d43f1f8f46d3c1eb16bd1ee9eef1d5254dcf87131af1ef80def6869734198cff3a189b22f2ff09b5f350be3bf94df0dbf2dda99c995bf22d42feaf6ab788fa3393cbff44930ff5f7984a0b4d7e4d8bb914f37b255feebe2f4bcd4b68b2b8433007f51176d0beff82f092aa7f9732fa432472eb7db67283dc7a01c4c0f087b7f4e66a309970968753424691dae9f0ed80f4ad945e6cffe59e3ddf5c89f985ce8464a32bd653de6bca26f2c03ba8fd13504b03040a0000000000433ee24200000000000000000000000006001c0074657374732f55540900030d6ad251eca8335275780b000104f50100000414000000504b0304140000000800433ee242e3d65566820100008902000007001c0074657374732f6355540900030d6ad2511aa8335275780b000104f501000004140000006550d16adc30107cd7574c74574820f1f90aa10f771728d4854020e1ce6f69203a59aed5d89291d66942e9bf772d5fda0b7dd2eeecccce8e66278bbd758bbd8a8d988919cac646d4b635e84da87de82214c84482af418d811ac8ff18aaef06f13592e9325c13b4720883c3cf4691793601af7e406d4c0bdbf53e907204f2d39e774bd851b92a615107db53f28e0c28820a06d6e976a84cc54562f5c1ef5bd3a157fa49b15e68265ea5cd99c67a5ddc7e15b383681da9b23e6bae8465ff4e59777a865f02d08d0a88f7cb3c7f58711bf9fcfa547e88f21cf16c44fac08a047d73f27cc282a12138e42bf15b8c2e47c67cdbe46c5d3f50a5482586286f361fc5b6b8db2c73a19bce57f87479892c5b68dff59c73a1c571f396223de6c5605edebcd7f11f4f9a54fc23be5dc102ccd950d81af7b8e09054f981723cacc6df731c835bbe71f3389e7f983e329c04723e4d254e36907fc3c823396074e3218bedf6762b27e0c512965cd65698361af11fe7c018e7695016bb12779f77bbe28b1469988b3f504b01021e030a0000000000433ee242000000000000000000000000080018000000000000001000ed4100000000636f6d706172652f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242a9caa15889040000cb0e0000090018000000000001000000ed8142000000636f6d706172652f6355540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242a9caa15889040000cb0e00000b0018000000000001000000ed810e050000636f6d706172652f63707055540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242a9caa15889040000cb0e00000c0018000000000001000000ed81dc090000636f6d706172652f6a61766155540500030d6ad25175780b000104f50100000414000000504b01021e030a000000000002732c43000000000000000000000000080018000000000000001000ed41ab0e0000636f6d70696c652f555405000364b2315275780b000104f50100000414000000504b01021e03140000000800433ee24279b6c08e5c070000e4110000090018000000000001000000a481ed0e0000636f6d70696c652f6355540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee2420dfbc7c056070000d61100000b0018000000000001000000a4818c160000636f6d70696c652f63707055540500030d6ad25175780b000104f50100000414000000504b01021e0314000000080002732c43b35fad82ec070000451300000c0018000000000001000000a481271e0000636f6d70696c652f6a617661555405000364b2315275780b000104f50100000414000000504b01021e03140000000800433ee24202c9277e58070000de1100000b0018000000000001000000a48159260000636f6d70696c652f70617355540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee2420000000000000000000000000c0018000000000000001000ed41f62d00006465736372697074696f6e2f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242ff36f6634700000050000000140018000000000001000000a4813c2e00006465736372697074696f6e2f626974732e74787455540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242578f11bd340000003d000000180018000000000001000000a481d12e00006465736372697074696f6e2f70726f626c656d2e696e666f55540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000060018000000000000001000ed41572f0000696e7075742f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee2422d30c7173f000000150800000a0018000000000001000000a481972f0000696e7075742f6269747355540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000070018000000000000001000ed411a3000006c696d6974732f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee24271deea4ec201000077030000080018000000000001000000a4815b3000006c696d6974732f6355540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee24271deea4ec2010000770300000a0018000000000001000000a4815f3200006c696d6974732f63707055540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee24271deea4ec2010000770300000b0018000000000001000000a481653400006c696d6974732f6a61766155540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000070018000000000000001000ed416c3600006f75747075742f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242578d8e84370000007f0000000b0018000000000001000000a481ad3600006f75747075742f6269747355540500030d6ad25175780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000040018000000000000001000ed412937000072756e2f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee2424a41584c17060000ef0d0000050018000000000001000000ed816737000072756e2f6355540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee2424a41584c17060000ef0d0000070018000000000001000000ed81bd3d000072756e2f63707055540500030d6ad25175780b000104f50100000414000000504b01021e031400000008006c0b2e43f347839861080000fa140000080018000000000001000000ed811544000072756e2f6a61766155540500035c9f335275780b000104f50100000414000000504b01021e030a0000000000433ee242000000000000000000000000060018000000000000001000ed41b84c000074657374732f55540500030d6ad25175780b000104f50100000414000000504b01021e03140000000800433ee242e3d655668201000089020000070018000000000001000000ed81f84c000074657374732f6355540500030d6ad25175780b000104f50100000414000000504b0506000000001a001a001a080000bb4e00000000');
SELECT pg_catalog.lo_close(0);

COMMIT;

--
-- Name: answertable answer_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.answertable
    ADD CONSTRAINT answer_pkey PRIMARY KEY (contestnumber, answernumber);


--
-- Name: bkptable bkp_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.bkptable
    ADD CONSTRAINT bkp_pkey PRIMARY KEY (contestnumber, sitenumber, bkpnumber);


--
-- Name: clartable clar_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.clartable
    ADD CONSTRAINT clar_pkey PRIMARY KEY (contestnumber, clarsitenumber, clarnumber);


--
-- Name: contesttable contest_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.contesttable
    ADD CONSTRAINT contest_pkey PRIMARY KEY (contestnumber);


--
-- Name: langtable lang_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.langtable
    ADD CONSTRAINT lang_pkey PRIMARY KEY (contestnumber, langnumber);


--
-- Name: logtable log_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.logtable
    ADD CONSTRAINT log_pkey PRIMARY KEY (lognumber);


--
-- Name: problemtable problem_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.problemtable
    ADD CONSTRAINT problem_pkey PRIMARY KEY (contestnumber, problemnumber);


--
-- Name: runtable run_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.runtable
    ADD CONSTRAINT run_pkey PRIMARY KEY (contestnumber, runsitenumber, runnumber);


--
-- Name: sitetable site_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.sitetable
    ADD CONSTRAINT site_pkey PRIMARY KEY (contestnumber, sitenumber);


--
-- Name: sitetimetable sitetime_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.sitetimetable
    ADD CONSTRAINT sitetime_pkey PRIMARY KEY (contestnumber, sitenumber, sitestartdate);


--
-- Name: tasktable task_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.tasktable
    ADD CONSTRAINT task_pkey PRIMARY KEY (contestnumber, sitenumber, tasknumber);


--
-- Name: usertable user_pkey; Type: CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.usertable
    ADD CONSTRAINT user_pkey PRIMARY KEY (contestnumber, usersitenumber, usernumber);


--
-- Name: answer_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX answer_index ON public.answertable USING btree (contestnumber, answernumber);


--
-- Name: bkp_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX bkp_index ON public.bkptable USING btree (contestnumber, sitenumber, bkpnumber);


--
-- Name: bkp_index2; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX bkp_index2 ON public.bkptable USING btree (contestnumber, sitenumber, usernumber);


--
-- Name: clar_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX clar_index ON public.clartable USING btree (contestnumber, clarsitenumber, clarnumber);


--
-- Name: clar_index2; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX clar_index2 ON public.clartable USING btree (contestnumber, clarsitenumber, usernumber);


--
-- Name: contestnumber_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX contestnumber_index ON public.contesttable USING btree (contestnumber);


--
-- Name: lang_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX lang_index ON public.langtable USING btree (contestnumber, langnumber);


--
-- Name: lang_index2; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX lang_index2 ON public.langtable USING btree (contestnumber, langname varchar_ops);


--
-- Name: log_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX log_index ON public.logtable USING btree (contestnumber, sitenumber, logdate);


--
-- Name: log_index2; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX log_index2 ON public.logtable USING btree (contestnumber, loguser, sitenumber);


--
-- Name: problem_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX problem_index ON public.problemtable USING btree (contestnumber, problemnumber);


--
-- Name: problem_index2; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX problem_index2 ON public.problemtable USING btree (contestnumber, problemname varchar_ops);


--
-- Name: run_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX run_index ON public.runtable USING btree (contestnumber, runsitenumber, runnumber);


--
-- Name: run_index2; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX run_index2 ON public.runtable USING btree (contestnumber, runsitenumber, usernumber);


--
-- Name: site_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX site_index ON public.sitetable USING btree (contestnumber, sitenumber);


--
-- Name: sitetime_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX sitetime_index ON public.sitetimetable USING btree (contestnumber, sitenumber, sitestartdate);


--
-- Name: sitetimesite_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE INDEX sitetimesite_index ON public.sitetimetable USING btree (contestnumber, sitenumber);


--
-- Name: task_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX task_index ON public.tasktable USING btree (contestnumber, sitenumber, tasknumber);


--
-- Name: user_index; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX user_index ON public.usertable USING btree (contestnumber, usersitenumber, usernumber);


--
-- Name: user_index2; Type: INDEX; Schema: public; Owner: bocauser
--

CREATE UNIQUE INDEX user_index2 ON public.usertable USING btree (contestnumber, usersitenumber, username varchar_ops);


--
-- Name: runtable answer_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.runtable
    ADD CONSTRAINT answer_fk FOREIGN KEY (contestnumber, runanswer) REFERENCES public.answertable(contestnumber, answernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sitetable contest_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.sitetable
    ADD CONSTRAINT contest_fk FOREIGN KEY (contestnumber) REFERENCES public.contesttable(contestnumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: problemtable contest_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.problemtable
    ADD CONSTRAINT contest_fk FOREIGN KEY (contestnumber) REFERENCES public.contesttable(contestnumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: answertable contest_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.answertable
    ADD CONSTRAINT contest_fk FOREIGN KEY (contestnumber) REFERENCES public.contesttable(contestnumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: langtable contest_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.langtable
    ADD CONSTRAINT contest_fk FOREIGN KEY (contestnumber) REFERENCES public.contesttable(contestnumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: runtable lang_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.runtable
    ADD CONSTRAINT lang_fk FOREIGN KEY (contestnumber, runlangnumber) REFERENCES public.langtable(contestnumber, langnumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: logtable loguser; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.logtable
    ADD CONSTRAINT loguser FOREIGN KEY (contestnumber, loguser, sitenumber) REFERENCES public.usertable(contestnumber, usernumber, usersitenumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: runtable problem_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.runtable
    ADD CONSTRAINT problem_fk FOREIGN KEY (contestnumber, runproblem) REFERENCES public.problemtable(contestnumber, problemnumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: clartable problem_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.clartable
    ADD CONSTRAINT problem_fk FOREIGN KEY (contestnumber, clarproblem) REFERENCES public.problemtable(contestnumber, problemnumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sitetimetable site_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.sitetimetable
    ADD CONSTRAINT site_fk FOREIGN KEY (contestnumber, sitenumber) REFERENCES public.sitetable(contestnumber, sitenumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: usertable site_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.usertable
    ADD CONSTRAINT site_fk FOREIGN KEY (contestnumber, usersitenumber) REFERENCES public.sitetable(contestnumber, sitenumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: logtable site_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.logtable
    ADD CONSTRAINT site_fk FOREIGN KEY (contestnumber, sitenumber) REFERENCES public.sitetable(contestnumber, sitenumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tasktable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.tasktable
    ADD CONSTRAINT user_fk FOREIGN KEY (contestnumber, sitenumber, usernumber) REFERENCES public.usertable(contestnumber, usersitenumber, usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: runtable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.runtable
    ADD CONSTRAINT user_fk FOREIGN KEY (contestnumber, runsitenumber, usernumber) REFERENCES public.usertable(contestnumber, usersitenumber, usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: clartable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.clartable
    ADD CONSTRAINT user_fk FOREIGN KEY (contestnumber, clarsitenumber, usernumber) REFERENCES public.usertable(contestnumber, usersitenumber, usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: bkptable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: bocauser
--

ALTER TABLE ONLY public.bkptable
    ADD CONSTRAINT user_fk FOREIGN KEY (contestnumber, sitenumber, usernumber) REFERENCES public.usertable(contestnumber, usersitenumber, usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: DATABASE bocadb; Type: ACL; Schema: -; Owner: postgres
--

GRANT ALL ON DATABASE bocadb TO bocauser;


--
-- PostgreSQL database dump complete
--

