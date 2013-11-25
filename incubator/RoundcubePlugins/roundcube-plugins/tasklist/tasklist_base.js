/**
 * Client scripts for the Tasklist plugin
 *
 * @version @package_version@
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * Copyright (C) 2012, Kolab Systems AG <contact@kolabsys.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
 
function rcube_tasklist(settings)
{
    /* private vars */
    var ui_loaded = false;
    var me = this;

    /*  public members  */
    this.ui;

    /* public methods */
    this.create_from_mail = create_from_mail;
    this.mail2taskdialog = mail2task_dialog;


    /**
     * Open a new task dialog prefilled with contents from the currently selected mail message
     */
    function create_from_mail()
    {
        var uid;
        if ((uid = rcmail.get_single_uid())) {
            // load calendar UI (scripts and edit dialog template)
            if (!ui_loaded) {
                $.when(
                    $.getScript('./plugins/tasklist/tasklist.js'),
                    $.getScript('./plugins/tasklist/jquery.tagedit.js'),
                    $.get(rcmail.url('tasks/inlineui'), function(html){ $(document.body).append(html); }, 'html')
                ).then(function() {
                    // register attachments form
                    // rcmail.gui_object('attachmentlist', 'attachmentlist');

                    ui_loaded = true;
                    me.ui = new rcube_tasklist_ui(settings);
                    create_from_mail();  // start over
                });
                return;
            }
            else {
                // get message contents for task dialog
                var lock = rcmail.set_busy(true, 'loading');
                rcmail.http_post('tasks/mail2task', {
                    '_mbox': rcmail.env.mailbox,
                    '_uid': uid
                }, lock);
            }
        }
    }

    /**
     * Callback function to put the given task properties into the dialog
     */
    function mail2task_dialog(prop)
    {
        this.ui.edit_task(null, 'new', prop);
    }

}

/* tasklist plugin initialization (for email task) */
window.rcmail && rcmail.env.task == 'mail' && rcmail.addEventListener('init', function(evt) {
    var tasks = new rcube_tasklist(rcmail.env.libcal_settings);

    rcmail.register_command('tasklist-create-from-mail', function() { tasks.create_from_mail() });
    rcmail.addEventListener('plugin.mail2taskdialog', function(p){ tasks.mail2taskdialog(p) });
    rcmail.addEventListener('plugin.unlock_saving', function(p){ tasks.ui && tasks.ui.unlock_saving(); });

    if (rcmail.env.action != 'show')
        rcmail.env.message_commands.push('tasklist-create-from-mail');
    else
        rcmail.enable_command('tasklist-create-from-mail', true);
});

