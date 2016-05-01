---
layout: post
title:  "Deploying an OVF Template From An Attached Datastore"
description: Instructions on how to deploy an OVF template that resides on an attached datastore
permalink: /deploy-ovf-from-attached-datastore/
---

The other day I ran into an issue trying to deploy a OVF from an attached datastore. I needed to do this because I was connected over VPN and didn't want to upload an OVF over the connection.

To do this, navigate to `https://<your_vsphere_host>/folder` and log in with the root credentials (Same root credentials used to log into the vSphere console):

<!--excerpt_separator-->

![OVF1](/assets/images/posts/2014-03-10-ovf1.png)

Select, `ha-datacenter`, and then the datastore where the OVF is located.  Navigate to the OVF file, right click and copy link address.

Open up the vSphere client, Deploy OVF template, and enter the url into the OVF file location.  When you click next, it will ask for the same credentials you were asked before when accessing the host via your web browser.

![OVF2](/assets/images/posts/2014-03-10-ovf2.png)

![OVF3](/assets/images/posts/2014-03-10-ovf3.png)
