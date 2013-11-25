require ["fileinto"];
# rule:[Spam]
if header :contains "X-Spam-Flag" "YES"
{
        fileinto "INBOX.Junk";
        stop;
}
