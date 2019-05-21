require ["fileinto","vacation"];
# rule:[spam]
if header :contains "X-Spam-Flag" "YES"
{
        fileinto "INBOX.Junk";
        stop;
}
# rule:[vacation]
if true
{
        vacation :days 1 :subject "Out of office" text:
Hello,

Thank you for your message. I'm out of office, with no email access.
.
;
}
