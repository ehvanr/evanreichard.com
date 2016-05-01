---
layout: post
title:  "Convert PowerShell HashTable to CSV File"
description: Convert a PowerShell HashTable to a CSV File
permalink: /convert-powershell-hashtable-to-csv-file
---

Unfortunately you can’t use the `Export-CSV` cmdlet to export a custom `HashTable` to a CSV file (Not directly) I found you could convert it to a `PSObject` which can be directly piped to `Export-CSV`.

{% highlight PowerShell %}
$OutputTable = $MyHashTable.getEnumerator() | foreach{
    New-Object PSObject -Property ([ordered]@{Computer = $_.Name;Time = $_.Value})
}

$OutputTable | Export-CSV test.csv -NoTypeInformation
{% endhighlight %}
