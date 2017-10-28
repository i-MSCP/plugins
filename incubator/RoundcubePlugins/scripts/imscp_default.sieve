require ["fileinto","vacation"];
# rule:[Spam]
if header :contains "X-Spam-Flag" "YES" {
    fileinto "INBOX.Junk";
    stop;
}
# rule:[Vacation]
if false # true
{
    vacation :days 1 :subject "Out of office" text:
Hello,

Thank you for your message. I'm currently out of office, with no email access.

Kind regards
.
;
}
