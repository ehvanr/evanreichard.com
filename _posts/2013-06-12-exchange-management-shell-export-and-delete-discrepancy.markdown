---
layout: post
title:  "Exchange Management Shell Export vs Delete Date Discrepancy"
description: New-MailboxExportRequest -ContentFilter and Search-Mailbox -SearchQuery date range is different between the two
permalink: /exchange-management-shell-export-and-delete-discrepancy
---

I discovered something interesting the other day when I was trying to export an Exchange 2010 journaling mailbox to a PST file through the Exchange Management Shell. The `-ContentFilter` flag for the `New-MailboxExportRequest` cmdlet **does not** act the same as the `-SearchQuery` flag for the `Search-Mailbox` cmdlet.


I would specify the date range of the New-MailboxExportRequest using the ContentFilter flag:

{% highlight PowerShell %}
New-MailboxExportRequest -Mailbox exch.journal -ContentFilter "(Received -ge '07/11/2012') -and (Received -le '07/11/2013')" -FilePath "\\SomeServer\SomeFolder\FileName_07112013-07112013.pst"
{% endhighlight %}

And then I would delete that date range from the journaling mailbox:

{% highlight PowerShell %}
Search-Mailbox -Identity exch.journal -SearchQuery "Received:>=$('07/11/2012') and Received:<=$('07/11/2013')" -DeleteContent
{% endhighlight %}

You’d think that I did nothing wrong but I just lost a days worth of my journal.  

<!--excerpt_separator-->

If you pipe the export request to get more statistics:

{% highlight PowerShell %}
Get-MailboxExportRequest | Get-MailboxExportRequestStatistics | ft ContentFilter
{% endhighlight %}

You’d see that the ContentFilter portion, which we specified above as equal to or less than `07/11/2013`, had a time of `00:00:00` appended to it.  Equal to or less than `07/11/2013` is much different than equal to or less than `07/11/2013 00:00:00` as one second before the latter is `07/10/2013 23:59:59`.

In order to get around this, make sure (especially if you’re automating this whole process, as I was) to specify the time:

{% highlight PowerShell %}
New-MailboxExportRequest -Mailbox exch.journal -ContentFilter "(Received -ge '07/11/2012 00:00:00') -and (Received -le '07/11/2013 23:59:59')" -FilePath "\\SomeServer\SomeFolder\FileName_07112013-07112013.pst"
{% endhighlight %}

This is a pretty big discrepancy, and I know that they’re two different flags but it should be expected that the two act the same; Especially when these commands are commonly run successively.
