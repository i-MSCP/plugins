/**
 * Client scripts for the Tasklist plugin
 *
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * @licstart  The following is the entire license notice for the
 * JavaScript code in this file.
 *
 * Copyright (C) 2013, Kolab Systems AG <contact@kolabsys.com>
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
 *
 * @licend  The above is the entire license notice
 * for the JavaScript code in this file.
 */
 
function rcube_tasklist(settings)
{
    /* private vars */
    var ui_loaded = false;
    var me = this;
    var mywin = window;

    /*  public members  */
    this.ui = null;

    /* public methods */
    this.create_from_mail = create_from_mail;
    this.mail2taskdialog = mail2task_dialog;
    this.save_to_tasklist = save_to_tasklist;


    /**
     * Open a new task dialog prefilled with contents from the currently selected mail message
     */
    function create_from_mail(uid)
    {
        if (uid || (uid = rcmail.get_single_uid())) {
            // load calendar UI (scripts and edit dialog template)
            if (!ui_loaded) {
                $.when(
                    $.getScript(rcmail.assets_path('plugins/tasklist/tasklist.js')),
                    $.get(rcmail.url('tasks/inlineui'), function(html) { $(document.body).append(html); }, 'html')
                ).then(function() {
                    // register attachments form
                    // rcmail.gui_object('attachmentlist', 'attachmentlist');

                    ui_loaded = true;
                    me.ui = new rcube_tasklist_ui($.extend(rcmail.env.tasklist_settings, settings));
                    create_from_mail(uid);  // start over
                });

                return;
            }

            // get message contents for task dialog
            var lock = rcmail.set_busy(true, 'loading');
            rcmail.http_post('tasks/mail2task', {
                '_mbox': rcmail.env.mailbox,
                '_uid': uid
            }, lock);
        }
    }

    /**
     * Callback function to put the given task properties into the dialog
     */
    function mail2task_dialog(prop)
    {
        this.ui.edit_task(null, 'new', prop);
        rcmail.addEventListener('responseaftertask', refresh_mailview);
    }

    /**
     * Reload the mail view/preview to update the tasks listing
     */
    function refresh_mailview(e)
    {
        var win = rcmail.env.contentframe ? rcmail.get_frame_window(rcmail.env.contentframe) : mywin;
        if (win && e.response.action == 'task') {
            win.location.reload();
        }
    }

    // handler for attachment-save-tasklist commands
    function save_to_tasklist()
    {
      // TODO: show dialog to select the tasklist for importing
      if (this.selected_attachment && window.rcube_libcalendaring) {
        rcmail.http_post('tasks/mailimportattach', {
            _uid: rcmail.env.uid,
            _mbox: rcmail.env.mailbox,
            _part: this.selected_attachment
            // _list: $('#tasklist-attachment-saveto').val(),
          }, rcmail.set_busy(true, 'itip.savingdata'));
      }
    }

    // register event handlers on linked task items in message view
    // the checkbox allows to mark a task as complete 
    if (rcmail.env.action == 'show' || rcmail.env.action == 'preview') {
        $('div.messagetasklinks input.complete').click(function(e) {
            var $this = $(this);
            $(this).closest('.messagetaskref').toggleClass('complete');

            // submit change to server
            rcmail.http_post('tasks/task', {
                action: 'complete',
                t: { id:this.value, list:$this.attr('data-list') },
                complete: this.checked?1:0
            }, rcmail.set_busy(true, 'tasklist.savingdata'));
        });
    }
}

/* tasklist plugin initialization (for email task) */
window.rcmail && rcmail.env.task == 'mail' && rcmail.addEventListener('init', function(evt) {
    var tasks = new rcube_tasklist(rcmail.env.libcal_settings);

    rcmail.register_command('tasklist-create-from-mail', function() { tasks.create_from_mail(); });
    rcmail.register_command('attachment-save-task', function() { tasks.save_to_tasklist(); });
    rcmail.addEventListener('plugin.mail2taskdialog', function(p) { tasks.mail2taskdialog(p); });
    rcmail.addEventListener('plugin.unlock_saving', function(p) { tasks.ui && tasks.ui.unlock_saving(); });

    if (rcmail.env.action != 'show')
        rcmail.env.message_commands.push('tasklist-create-from-mail');
    else
        rcmail.enable_command('tasklist-create-from-mail', true);

    rcmail.addEventListener('beforemenu-open', function(p) {
        if (p.menu == 'attachmentmenu') {
            tasks.selected_attachment = p.id;
            var mimetype = rcmail.env.attachments[p.id],
                is_ics = mimetype == 'text/calendar' || mimetype == 'text/x-vcalendar' || mimetype == 'application/ics';

            rcmail.enable_command('attachment-save-task', is_ics);
        }
    });
});
