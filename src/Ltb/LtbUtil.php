<?php namespace Ltb;

# factorisation functions, may use globals
# DO NOT USE this is a temporary class during rework
# globals define ldap context and behavior will be moved in LDAPContext later.
final class LtbUtil {

    static function search($ldap_filter,$attributes)
    {

        global $ldap_url, $ldap_starttls, $ldap_binddn, $ldap_bindpw, $ldap_network_timeout;
        global $ldap_user_base, $ldap_size_limit;
        global $attributes_map,$search_result_title,$search_result_sortby,$search_result_items,$search_result_sortby;

        $result = "";
        $nb_entries = 0;
        $entries = array();
        $size_limit_reached = false;

        # Connect to LDAP
        $ldap_connection = \Ltb\Ldap::connect($ldap_url, $ldap_starttls, $ldap_binddn, $ldap_bindpw, $ldap_network_timeout, null);

        $ldap = $ldap_connection[0];
        $result = $ldap_connection[1];

        if ($ldap) {

            foreach( $search_result_items as $item ) {
                $attributes[] = $attributes_map[$item]['attribute'];
            }
            $attributes[] = $attributes_map[$search_result_title]['attribute'];
            $attributes[] = $attributes_map[$search_result_sortby]['attribute'];

            # Search for users
            $search = \Ltb\PhpLDAP::ldap_search($ldap, $ldap_user_base, $ldap_filter, $attributes, 0, $ldap_size_limit);

            $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

            if ( $errno == 4) {
                $size_limit_reached = true;
            }
            if ( $errno != 0 and $errno !=4 ) {
                $result = "ldaperror";
                error_log("LDAP - Search error $errno  (".\Ltb\PhpLDAP::ldap_error($ldap).")");
            } else {

                # Get search results
                $nb_entries = \Ltb\PhpLDAP::ldap_count_entries($ldap, $search);

                if ($nb_entries === 0) {
                    $result = "noentriesfound";
                } else {
                    $entries = \Ltb\PhpLDAP::ldap_get_entries($ldap, $search);

                    # Sort entries
                    if (isset($search_result_sortby)) {
                        $sortby = $attributes_map[$search_result_sortby]['attribute'];
                        \Ltb\Ldap::ldapSort($entries, $sortby);
                    }

                    unset($entries["count"]);
                }
            }
        }

        return [$ldap,$result,$nb_entries,$entries,$size_limit_reached];

    }
}


