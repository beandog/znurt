CREATE OR REPLACE FUNCTION package_id(atom varchar, OUT id integer) AS $$
DECLARE 
	c varchar;
	p varchar;
BEGIN
	c := category_name(atom);
	p := package_name(atom);
	id := package_id(c, p);	
END;
$$ language plpgsql;

CREATE OR REPLACE FUNCTION category_id(atom varchar, OUT i integer) AS $$
	DECLARE c varchar;
BEGIN
	c := category_name(atom);
	i := id FROM category WHERE name = c;
END;
$$ language plpgsql;

CREATE OR REPLACE FUNCTION category_name (str varchar, OUT v varchar) AS $$ BEGIN IF POSITION('/' IN str) > 0 THEN
v := regexp_replace(str, E'^(!{1,2})?(>|<)?(~|=)?', '');
v := regexp_replace(v, E'/.*', '');
ELSE
v := str;
END IF;
END;
$$ language plpgsql;

CREATE OR REPLACE FUNCTION package_name(str varchar, OUT package_name varchar) AS $$
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
$$ language plpgsql;


CREATE OR REPLACE FUNCTION package_description(package_id int, OUT package_description text) AS $$
BEGIN

package_description := em.value FROM ebuild e INNER JOIN package p ON e.package = p.id AND e.package = package_id INNER JOIN ebuild_metadata em ON em.ebuild = e.id AND em.keyword::text = 'description'::text WHERE e.id = (( SELECT e2.id FROM ebuild e2 WHERE e2.package = package_id ORDER BY e2.cache_mtime DESC, e2.ev DESC,  e2.lvl DESC, e2.p IS NULL, e2.p DESC, e2.rc IS NULL, e2.rc DESC, e2.pre IS NULL, e2.pre DESC, e2.beta IS NULL, e2.beta DESC, e2.alpha IS NULL, e2.alpha DESC, e2.pr IS NULL, e2.pr DESC LIMIT 1));

END;
$$ language plpgsql;