---
layout: post
title:  "Determining VM’s not protected by any Veeam Backup Job"
description: Determining VM’s not protected by any Veeam Backup Job
permalink: /determining-vms-not-protected-by-any-veeam-backup-job
---

**You can skip to the script below if you’d like, but I recommend setting up a separate user and the following permissions if you plan to run it as a scheduled task.**

I needed to create a script that automatically detected any VM’s that were not being backed up on any of the three Veeam Backup and Replication servers we had.  This script reads an exception list where you can list VM names as exceptions so it will not report them as not backed up.

I specifically created a separate Unprivileged AD user that would run this scheduled task, these are the instructions on how to apply the appropriate permissions.

<!--excerpt_separator-->

Allow PowerShell Remoting with a Non-Admin User
===============================================

Because this script uses PowerShell remoting, you need to grant execute permissions (Contrary to popular belief, you do not have to be an Administrator in order to PS Remote) On all the Veeam machines (in which we’re going to Remote PS), in an Administrative PowerShell session execute:


{% highlight PowerShell %}
Set-PSSessionConfiguration -Name Microsoft.PowerShell -showSecurityDescriptorUI
{% endhighlight %}

Give your Unprivileged User Execute rights.

We need to now grant the Unprivileged User Execute and Read permissions on the VeeamBackup SQL Database.  In my situation, we were running a MS SQL 2005 DB so these instructions may vary if you have a different setup.


Setup Remote Access to a SQL Server 2005 DB
===========================================

On all Veeam B&R Machines, go to “SQL Server Config. Manager” -> “SQL Server 2005 Network Config” -> “Protocols for VEEAM”, right click and enable “TCP/IP”

Restart the SQL Instance Server and start the SQL Browser Service.

Add these firewall rules (Domain Restricted):

- [Port] Open TCP Port 1433
- [Program] Permit SQL Server
- [Program] Permit SQL Browser

You should now connect to the SQL Instance with SQL Server Management Studio (SSMS)

Grant Appropriate DB Permissions to the Unprivileged User
=========================================================

Create an “Execute” role in the “VeeamBackup” DB:

{% highlight SQL %}
CREATE ROLE "db_execute"
GRANT EXECUTE TO "db_execute"
{% endhighlight %}

Right click “Logins” under “Security” and select “New Login”

Add the Unprivileged User and jump to “User Mapping” and select the “VeeamBackup” database. Select the “db_datareader” and “db_execute” (The role we created in the previous step). Save.

Grant Read Only Permission in vCenter to the Unprivileged User
==============================================================

Connect to your vCenter Server with vSphere, right click your vCenter Server in “Hosts and Clusters” view, select “Add Permission” and grant Read Only permissions to the AD User.

Now we should be good to go, set up Task Scheduler to run this PowerShell Script as the AD Unprivileged User.

**Note:** vSphere PowerCLI has to be installed on the machine that runs this script. (As apparent by the Add-PSSnapIn cmdlet)

[This script on GitHub][1]

{% highlight PowerShell %}

# Written by Evan Reichard (September 2013)

# Add Appropriate PSSnapin
Add-PSSnapin VMware.VimAutomation.Core

# vCenter Server
$vCenterServer = "vCenter"

# Appropriate Arrays / Hashtable
$VeeamMachines = @("veeam1", "veeam2", "veeam3")
$NotProtectedList = @()
$ProtectedList = @()
$ExceptionList = @()
$ExportTable = @{}

# Email Addresses and SMTP Server
# Sorry, had to get rid of the @ in the email address - WordPress didn't like it.
$toEmailAddress = "JDoe(at)example.com"
$fromEmailAddress = "AuditVMBackup(at)example.com"
$smtpServer = "smtp.example.com"

# Exception full file path
$ExceptionFileDir = "C:\scripts\vmexception.txt"
$OutputFileDir = "C:\scripts\VMAudit.csv"

# Creates ExceptionList array that holds all machines specified in the exception file dir
Get-Content $ExceptionFileDir | Foreach-Object {
	$ExceptionList += $_
}

# --------------- Needs Veeam Backup PowerShell Toolkit ---------------

# Cycles through all Veeam Servers in $VeeamMachines
foreach($VeeamServer in $VeeamMachines){

	# Remote PS Command
	$VeeamProtectedList = Invoke-Command -ComputerName $VeeamServer -ScriptBlock{
		Add-PSSnapin VeeamPSSnapIn

		$VeeamProtectedList = @()

		$Jobs = Get-VBRJob
		foreach ($Job in $Jobs){
			$VMS = $Job.GetObjectsInJob()

			foreach ($VM in $VMS){
				$VeeamProtectedList += $VM.Name
			}
		}

		$VeeamProtectedList
	}

	$ProtectedList += $VeeamProtectedList
}

# Sorts the $ProtectedList array for faster comparing between $CompleteList
$ProtectedList = $ProtectedList | Sort-Object

# -------------------------- Needs PowerCLI --------------------------

# Connects to the vCenter server
Connect-VIServer $vCenterServer

# Acquires a list of all the VM's that are currently powered on in vCenter.
$CompleteList = Get-View -ViewType "VirtualMachine" -Property Name -Filter @{"Runtime.PowerState"="PoweredOn"} | Select Name | Sort-Object Name

# Disconnects - We don't need to be connected anymore. 
Disconnect-VIServer -confirm:$false

# Compares $CompleteList with $ProtectedList and $ExceptionList to create the table
foreach($VM in $CompleteList){
	$VM = $VM.Name

	if($ProtectedList -notcontains $VM){
		if($ExceptionList -contains $VM){
			$ExportTable.Add($VM, "EXCEPTION")
		}else{
			$NotProtectedList += $VM
			$ExportTable.Add($VM, "NOT PROTECTED")
		}
	}else{
		$ExportTable.Add($VM, "PROTECTED")
	}
}

# NOTE! If the ProtectedList contains VM's that are not currently powered on, 
# then $NotProtectedList.Count + $ProtectedList.Count will exceed 
# $CompleteList.Count (I had initially thought this was a bug)

# Sorts ExportTable
$ExportTable = $ExportTable.GetEnumerator() | Sort-Object Value, Name

# Converts to it can easily be exported using Export-CSV
$CSVTable = $ExportTable.GetEnumerator() | foreach{
	New-Object PSObject -Property (@{Computer = $_.Name;Status = $_.Value})
}

# Converts so it can easily be converted to HTML
$HTMLTable = $NotProtectedList.GetEnumerator() | foreach{
	New-Object PSObject -Property (@{Computer = $_})
}

# Exports the table to a CSV file
$CSVTable | Export-CSV $OutputFileDir -NoTypeInformation

# Sends an email if there's a VM in the $NotProtectedList variable
if($NotProtectedList.Count -gt 0){
	$emailBody = $HTMLTable | ConvertTo-HTML | Out-String
	$emailBody = $emailBody -replace "<th>\*</th>","<th>Computer</th>"
	$emailBody += "<br/><p>Exception List: \\$vCenterServer\c$\scripts\vmexception.txt</p><p>CSV of the whole audit: \\$vCenterServer\c$\scripts\VMAudit.csv</p>"

	send-mailmessage -to $toEmailAddress -from $fromEmailAddress -subject "Audit VM Backup - VM(s) are not protected!" -body $emailBody -bodyashtml -smtpserver $smtpServer
}
{% endhighlight %}

This script will email the VM’s that are not backed up by any Veeam B&R Job on any of the servers within the `$VeeamMachines` array to `$toEmailAddress` from `$fromAddress` via the SMTP server at `$smtpServer`. It will also create a full report including exception, protected, and not protected VM’s on the vCenter machine in the `C:\scripts\VMAudit.csv` file.

[1]: https://github.com/evreichard/Scripts/blob/master/VMVeeamBackupAudit.ps1
