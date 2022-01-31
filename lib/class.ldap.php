<?php

class M16_Events_LDAP {
    private static $instance;

    protected function __construct() {

    }


    /**
     * Return the authors LDAP department, or the moderator override set via ACF.
     *
     * @param $post
     *
     * @return string|null
     */
    public static function get_ldap_department(WP_Post $post) {
        //$dept = get_term_by('id', get_field('acf_override_author_depatment', $post->ID), 'event_departments')->name;
        $dept = get_field('acf_override_author_department', $post->ID);
        if ( ! $dept) {
            $pretty_depts = get_ldap_departments();
            $depts        = array();
            if ($user = M16_Events_LDAP::getInfoForPerson($post->user)) {
                if (isset($user['departmentnumber']) && $pretty_depts[ $user['departmentnumber'] ]) {
                    array_push($depts, $pretty_depts[ $user['departmentnumber'] ]['name']);
                }
                for ($i = 2; $i <= 5; $i++) {
                    if (isset($user[ 'departmentnumber' . $i ]) && $pretty_depts[ $user[ 'departmentnumber' . $i ] ]) {
                        array_push($depts, $pretty_depts[ $user[ 'departmentnumber' . $i ] ]['name']);
                    }
                }
                $dept = join(', ', $depts);
            }
        }

        return $dept;
    }

    /**
     * This is a collection of functions useful for getting information out
     * of the public (white pages) LDAP
     *
     * @param string $username
     *
     * @return array|int an array of all publicly available info for that person
     */
    public static function getInfoForPerson($username = '') {
        $debug = 0;

        if ($username == '') {
            if ($debug) {
                echo "\n";
            }

            return 0;
        }

        //New 7/28/09
        $ldap_server   = '';      //LDAP Server
        $ldap_readDN   = '';//define("LDAP_SERVER_PORT","389");                          //LDAP Port
        $ldap_readPass = '';
        $ldap_port     = '';

        //$link = ldap_connect($ldap_server, $ldap_port);
        if ($link) {
            if ($debug) {
                echo "connection successful\n";
            }
            $bind = ldap_bind($link, $ldap_readDN, $ldap_readPass);
            if ($bind) {
                if ($debug) {
                    echo "bind successful\n";
                }
                $search = ldap_search($link, 'ou=people,o=williams', "(uid=$username)");
                if ($search) {
                    if ($debug) {
                        echo "search created\n";
                    }
                    $results = ldap_get_entries($link, $search);
                    if ($results) {
                        if ($debug) {
                            echo "got results\n";
                        }
                        if ($debug) {
                            print_r($results);
                        }
                        $results_array = array();
                        for ($j = 0; $j < $results[0]['count']; $j++) {
                            $results_array[ $results[0][ $j ] ] = $results[0][ $results[0][ $j ] ][0];
                        }
                        ldap_free_result($search);
                        ldap_close($link);

                        return $results_array;
                    }
                }
            }
            ldap_close($link);
        }

        if ($debug) {
            echo "fall through - returning 0\n";
        }

        return 0;
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_LDAP The singleton instance.
     */
    public static function instance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * singleton instance.
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Private unserialize method to prevent unserializing of the singleton
     * instance.
     *
     * @return void
     */
    private function __wakeup() {
    }
}

M16_Events_LDAP::instance();
