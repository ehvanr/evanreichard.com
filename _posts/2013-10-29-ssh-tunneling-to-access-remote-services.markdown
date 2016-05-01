---
layout: post
title:  "SSH Tunneling to Access Remote Services (ESXi and IPMI)"
description: Accessing ESXi via the vSphere Client and IPMI over SSH Tunneling
permalink: /ssh-tunneling-to-access-remote-services/
---

Awhile back I built an ESXi server and because I’m up at school, I have to be able to do 99% of things 400 miles away.  Up until now, I’ve been establishing an SSH session with my router (ASUS RT-N16 running Tomato) and then from there, SSH-ing into my ESXi server where I can execute esxcli commands to power up my VPN server.

Using SSH tunneling, I’m able to manage the IPMI & KVM web interface as well as use vSphere Client to connect to my ESXi server without using a VPN of any sort.  This is useful if for some reason your VM’s set to auto power on fail, or if you need to manually power on the server.

<!--excerpt_separator-->

ESXi vSphere Instructions
=========================

In PuTTY, forward ports `443`, `902`, and `903` to your ESXi’s IP Address (`10.0.40.122` in my case) Be sure to add `:<port>` to the end of the destination field (E.g. `10.0.40.133:903` in the example below)

![SSHForwarding1](/assets/images/posts/2013-10-29-sshforwarding1.png)

Open the connection and log in.  Ports `443`, `902` and `903` on `localhost` will now forward across the SSH connection to their respective ports on the ESXi server (`10.0.40.122` in my case)

At this point you’d think that you can just pop in localhost in the vSphere Client and be good to go.  It should be this way but for some reason the vSphere Client doesn’t want to do it that way.

To resolve this, you have to open the hosts file and add a name to point to `127.0.0.1`

Open `%SYSTEMROOT%\System32\drivers\etc\hosts` and perform the following changes (“ESXI” was arbitrarily chosen)

![SSHForwarding2](/assets/images/posts/2013-10-29-sshforwarding2.png)

You can now connect to your ESXi server :smile:

![SSHForwarding3](/assets/images/posts/2013-10-29-sshforwarding3.png)

IPMI & KVM Web Interface Instructions
=====================================

As the same as above, in PuTTY, forward ports `443`, `5900`, and `80` to your IMPI NIC’s IP Address (`10.0.40.149` in my case)

![SSHForwarding4](/assets/images/posts/2013-10-29-sshforwarding4.png)

And then you’re able to access the web interface :smile:

![SSHForwarding5](/assets/images/posts/2013-10-29-sshforwarding5.png)

There’s also a program called **IPMITool** from Supermicro which uses port `623`.  Unfortunately, this uses UDP as opposed to TCP, which can’t be conventionally tunneled over an SSH tunnel.  It’s possible by creating a UDP -> TCP bridge on the local machine, and then a TCP – > UDP bridge on the remote SSH machine. (Basically encapsulating UDP in TCP then converting it back)  I’ll probably go over this in another post at a later time.
