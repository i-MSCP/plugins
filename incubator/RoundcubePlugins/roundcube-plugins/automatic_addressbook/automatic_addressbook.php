<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Automatic address book
 *
 *
 * Simple plugin to register to "collect" all recipients of sent mail
 * to a dedicated address book (usefull for autocompleting email you
 * already used). User can choose in preferences (compose group) to
 * enable or disable the feature of this plugin.
 * Aims to reproduce the similar features of thunderbird or gmail.
 *
 * @version 0.4
 * @author Jocelyn Delalande (slightly modified by Roland 'rosali' Liebl)
 * @author Sebastien Blaisot <sebastien@blaisot.org>
 * @website https://github.com/sblaisot/automatic_addressbook
 * @licence http://www.gnu.org/licenses/gpl-3.0.html GNU GPLv3+
 *
 */

 /*
 * Skeletton based on "example_addressbook" plugin.
 * Contact adding code inspired by addcontact.inc by Thomas Bruederli
 */

class automatic_addressbook extends rcube_plugin
{
    public $task = 'mail|addressbook|settings';
    private $abook_id = 'collected';
  
    /**
     * Initialize plugin
     */
    public function init()
    {
        $this->add_hook('addressbooks_list', array($this, 'address_sources'));
        $this->add_hook('addressbook_get', array($this, 'get_address_book'));
        $this->add_hook('message_sent', array($this, 'register_recipients'));
        $this->add_hook('preferences_list', array($this, 'settings_table'));
        $this->add_hook('preferences_save', array($this, 'save_prefs'));
        $this->add_hook('contact_update', array($this, 'handle_doubles'));
        $this->add_hook('contact_create', array($this, 'handle_doubles'));
       
        $this->add_texts('localization/', false);
        $this->load_config('config/config.inc.php.dist');
        if (file_exists("./plugins/automatic_addressbook/config/config.inc.php")) {
            $this->load_config('config/config.inc.php');
        }

        // Adds an address-book category in rc <= 0.5, retro-compatibility code, 
        // not needed for rc 0.6
        $this->add_hook('preferences_sections_list', array($this, 'add_addressbook_category'));


        // use this address book for autocompletion queries
        $config = rcmail::get_instance()->config;
        $sources = $config->get('autocomplete_addressbooks', array('sql'));
        
        if (!in_array($this->abook_id, $sources) &&
                      $config->get('use_auto_abook', true) &&
                      $config->get('use_auto_abook_for_completion', true)) {
            $sources[] = $this->abook_id;
            $config->set('autocomplete_addressbooks', $sources);
        }
    }
    
    /**
     * Register automatic_addressbook as address source
     *
     * @param  array $p  Hash array with list of available address books
     * @return array $p  Hash array with list of available address books
     */
    public function address_sources($p)
    {
        $rcmail = rcmail::get_instance();
        if ($rcmail->config->get('use_auto_abook', true)) {
            $p['sources'][$this->abook_id] = 
                array('id' => $this->abook_id,
                      'name' => rcube_utils::rep_specialchars_output($this->gettext('automaticallycollected')),
                      'readonly' => FALSE, 'groups' => false);
        }

        return $p;
    }
  
    /**
     * Requests automatic_addressbook instance
     *
     * @param  array $p  Hash array containing the id of the requested abook
     * @return array $p  Hash array containing instance of the requested abook
     */
    public function get_address_book($p)
    {
        $rcmail = rcmail::get_instance();
        if (($p['id'] === $this->abook_id) && $rcmail->config->get('use_auto_abook', true)) {
            require_once dirname(__FILE__) . '/automatic_addressbook_backend.php';
            $p['instance'] = new automatic_addressbook_backend($rcmail->db, $rcmail->user->ID);
            $p['instance']->groups = false;
        }
        return $p;
    }

    /**
     * Collect the email address of a just-sent email recipients into
     * the automatic addressbook (if it's not already in another
     * addressbook). 
     *
     * @param array $p Hash array containing header and body of sent mail
     * @return nothing
     */
    public function register_recipients($p)
    {
        $rcmail = rcmail::get_instance();
    
        if (!$rcmail->config->get('use_auto_abook', true)) {
            return;
        }
    
        $headers = $p['headers'];

        if (!class_exists('rcube_mime')) { // RC < 0.8 compatibility code
            $IMAP = new rcube_imap(null);
            $all_recipients = array_merge(
                $IMAP->decode_address_list($headers['To'], null, true, $headers['charset']),
                $IMAP->decode_address_list($headers['Cc'], null, true, $headers['charset']),
                $IMAP->decode_address_list($headers['Bcc'], null, true, $headers['charset'])
            );
        } else {
            $all_recipients = array_merge(
                rcube_mime::decode_address_list($headers['To'], null, true, $headers['charset']),
                rcube_mime::decode_address_list($headers['Cc'], null, true, $headers['charset']),
                rcube_mime::decode_address_list($headers['Bcc'], null, true, $headers['charset'])
            );
        }

        require_once dirname(__FILE__) . '/automatic_addressbook_backend.php';
        $CONTACTS = new automatic_addressbook_backend($rcmail->db, $rcmail->user->ID);
    
        foreach ($all_recipients as $recipient) {
            // Bcc and Cc can be empty
            if ($recipient['mailto'] != '') {
                $contact = array(
                    'email' => $recipient['mailto'],
                    'name' => $recipient['name']
                    );

                // use email address part for name
                if (empty($contact['name']) || $contact['name'] == $contact['email']) {
                    $contact['name'] = ucfirst(preg_replace('/[\.\-]/', ' ',
                                               substr($contact['email'], 0, strpos($contact['email'], '@'))));
                }

                /* We only want to add the contact to the collected contacts
                 * address book if it is not already in an addressbook, so we
                 * first lookup in every address source.
                 */
                $book_types = (array)$rcmail->config->get('autocomplete_addressbooks', 'sql');

                foreach ($book_types as $id) {
                    $abook = $rcmail->get_address_book($id);
                    $previous_entries = $abook->search('email', $contact['email'], false, false);
      
                    if ($previous_entries->count) {
                        break;
                    }
                }
                if (!$previous_entries->count) {
                    $plugin = $rcmail->plugins->exec_hook('contact_create', array('record' => $contact,
                                                                                  'source' => $this->abook_id));
                    if (!$plugin['abort']) {
                        $CONTACTS->insert($contact, false);
                    }
                }
            }
        }
    }
  

    /** 
     * Adds an address-book settings category in rc <= 0.5, not needed for rc >= 0.6
     *
     * @param array $args Hash array with list (hash array) of sections and  list (hash array) of sections
     * @return array Hash array with list (hash array) of sections and  list (hash array) of sections
     */
    public function add_addressbook_category($args)
    {
        $temp = $args['list']['server'];
        unset($args['list']['server']);
        $args['list']['addressbook']['id'] = 'addressbook';
        $args['list']['addressbook']['section'] = $this->gettext('addressbook');
        $args['list']['server'] = $temp;
        
        return $args;
    }


    /**
     * Adds a check-box to enable/disable automatic address collection.
     *
     * @param array $args Hash array containing section and preference blocks
     * @return array Hash array containing preference blocks with addressbook preferences
     */
    public function settings_table($args) 
    {
        if ($args['section'] == 'addressbook') {
            $use_auto_abook = rcmail::get_instance()->config->get('use_auto_abook', true);
            $field_id = 'rcmfd_use_auto_abook';

            $checkbox = new html_checkbox(array(
                'name' => '_use_auto_abook', 
                'id' => $field_id, 'value' => 1
            ));
            $args['blocks']['automaticallycollected']['name'] = $this->gettext('automaticallycollected');
            $args['blocks']['automaticallycollected']['options']['use_subscriptions'] = array(
                'title' => html::label($field_id, rcube_utils::rep_specialchars_output($this->gettext('useautoabook'))),
                'content' => $checkbox->show($use_auto_abook ? 1 : 0),
            );

            $use_auto_abook_for_completion = rcmail::get_instance()->config->get('use_auto_abook_for_completion', true);
            $field_id2 = 'rcmfd_use_auto_abook_for_completion';
            $checkbox2 = new html_checkbox(array(
                           'name' => '_use_auto_abook_for_completion',
                               'id' => $field_id2, 'value' => 1
                         ));
            $args['blocks']['automaticallycollected']['name'] = $this->gettext('automaticallycollected');
            $args['blocks']['automaticallycollected']['options']['use_autocompletion'] = array(
                'title' => html::label($field_id2, rcube_utils::rep_specialchars_output($this->gettext('useforcompletion'))),
                'content' => $checkbox2->show($use_auto_abook_for_completion ? 1 : 0),
            );
        }
        return $args;
    }

    /**
     * Save preferences
     *
     * @param array $args Hash array with prefs to be saved 
     * @return array $args Hash array with result: boolean, abort: boolean, prefs: array 
     */
    public function save_prefs($args) 
    {
        if ($args['section'] == 'addressbook') {
            $rcmail = rcmail::get_instance();
            $use_auto_abook = $rcmail->config->get('use_auto_abook');
            $args['prefs']['use_auto_abook'] = isset($_POST['_use_auto_abook']) ? true : false;
            $use_auto_abook_for_completion = $rcmail->config->get('use_auto_abook_for_completion');
            $args['prefs']['use_auto_abook_for_completion'] = isset($_POST['_use_auto_abook_for_completion']) ? true : false;
        }
        return $args;
    }

    /**
     * When a contact is added to a "regular" addressbook, take care to
     * delete it from collected addressbook if it was in.
     *
     * @param array $args Hash array with contact details
     * @return array record updated or abort
     */
    public function handle_doubles($args) 
    {
        $rcmail = rcmail::get_instance();
        if (!$rcmail->config->get('use_auto_abook', true)) {
            return $args;
        }
        $moveto = $rcmail->config->get('on_edit_move_to_default');

        if ($args['source'] == $this->abook_id && !empty($args['id']) && $moveto) {
            $args['source'] = $rcmail->config->get('default_addressbook');
            $plugin = $rcmail->plugins->exec_hook('contact_create', array('record' => $args['record'],
                                                                          'source' => $args['source']));
            if (!$plugin['abort']) {
                $CONTACTS = $rcmail->get_address_book($args['source']);
                $insert_id = $CONTACTS->insert($args['record'], false);
            } else {
                $insert_id = $plugin['result'];
            }
            $rcmail->output->show_message('automatic_addressbook.contactmoved', 'confirmation');
            $rcmail->output->command('parent.list_contacts');
            return array('abort' => true, 'result' => $insert_id);
        }

        if ($args['source'] !== $this->abook_id) {
            foreach (array('email:home', 'email:work', 'email:other') as $email_field) {
                // Would trigger a warning with rc 0.5 without this if
                if ($args['record'][$email_field]) {
                    foreach ($args['record'][$email_field] as $email) {
                        $contact_emails[] = $email;
                    }
                }
            }
            // rc <= 0.5, retro-compatibility code, not needed for rc 0.6
            $contact_emails[] = $args['record']['email'];
            //

            foreach ($contact_emails as $contact_email) {
                if (!empty($contact_email)) {
                    $auto_abook = $rcmail->get_address_book($this->abook_id);
                    $auto_abook->reset();
                    $collected_contact = $auto_abook->search('email', $contact_email, false, true);
                    while ($record = $collected_contact->iterate()) {
                        $plugin = $rcmail->plugins->exec_hook('contact_delete', array('id' => $record['contact_id'],
                                                                                      'source' => $this->abook_id));
                        if (!$plugin['abort']) {
                            $auto_abook->delete($record['contact_id']);
                            $rcmail->output->show_message('automatic_addressbook.contactremoved', 'confirmation');
                        }
                    }
                }
            }
            if ($rcmail->task == "addressbook" &&
                $rcmail->action == "copy" &&
                $_REQUEST['_source'] == $this->abook_id) {
                $rcmail->output->command('parent.list_contacts');
            }
        }

        return $args;
    }
}
