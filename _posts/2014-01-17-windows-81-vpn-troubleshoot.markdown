---
layout: post
title:  "Windows 8.1 - General Failure While Connected to VPN"
description: Issue connecting to some hosts while connected over VPN
permalink: /windows-81-vpn-troubleshoot
---

I just had this issue where I was unable to connect to some publicly accessible hosts while connected to a VPN. I was unable to access a website and upon pinging it, “General Failure” was returned. It didn’t matter whether I checked “Use default gateway on remote network” or not.

I found the solution was to set the metric to a larger value than everything else. I set it to `5000`.

<!--excerpt_separator-->

You can set the appropriate metric in the following location:

![VPNMetric1](/assets/images/posts/2014-01-17-vpnmetric1.png)
