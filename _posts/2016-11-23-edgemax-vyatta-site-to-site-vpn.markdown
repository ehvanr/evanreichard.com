---
layout: post
title:  "EdgeMAX / Vyatta Site to Site VPN"
description: Instructions on how to deploy a site to site VPN between two EdgeRouter Lites
permalink: /edgemax-vyatta-site-to-site-vpn/
---

I recently moved and now have servers in two locations across the country (NY and AR)! Both networks' edge routers are the EdgeRouter Lite (see what I did there) made by Ubiquiti. These routers run a modified version of Vyatta with a web GUI.  Typically all CLI commands are cross compatible, but there are a few circumstances where they aren't.

I set up a site-to-site VPN between the two in addition to L2TP/IPSec PSK Authentication on one of the EdgeRouters.  This is how I set it up.

<!--excerpt_separator-->

First and foremost, to prevent us from having to deal with too much firewall configuration, lets set up auto firewall and nat exclude:
{% highlight Bash %}
set vpn ipsec auto-firewall-nat-exclude enable
{% endhighlight %}

Set the VPN auto update to 1 minute:
{% highlight Bash %}
set vpn ipsec auto-update 60
{% endhighlight %}

Configure the ESP Group.  Note that "ESP-1W" is an arbitrarily chosen name:
{% highlight Bash %}
set vpn ipsec esp-group ESP-1W proposal 1
set vpn ipsec esp-group ESP-1W proposal 1 encryption aes256
set vpn ipsec esp-group ESP-1W proposal 1 hash sha1
set vpn ipsec esp-group ESP-1W compression disable
set vpn ipsec esp-group ESP-1W lifetime 3600
set vpn ipsec esp-group ESP-1W mode tunnel
set vpn ipsec esp-group ESP-1W pfs enable
{% endhighlight %}

Configure the IKE Group. Again, "IKE-1W" is arbitrarily chosen:
{% highlight Bash %}
set vpn ipsec ike-group IKE-1W proposal 1
set vpn ipsec ike-group IKE-1W proposal 1 encryption aes256
set vpn ipsec ike-group IKE-1W proposal 1 hash sha1
set vpn ipsec ike-group IKE-1W proposal 1 dh-group 2
{% endhighlight %}

Lets configure dead peer detection. This allows the VPN to detect if there is a dead IKE peer and restart the connection:
{% highlight Bash %}
set vpn ipsec ike-group IKE-1W proposal 1 dead-peer-detection action restart
set vpn ipsec ike-group IKE-1W proposal 1 dead-peer-detection action interval 30
set vpn ipsec ike-group IKE-1W proposal 1 dead-peer-detection timeout 120
{% endhighlight %}

Set the appropriate IPSec interface (Typically the WAN interface):
{% highlight Bash %}
set vpn ipsec ipsec-interfaces interface eth0
{% endhighlight %}

Set the VPN to enable NAT traversal. This allows IPSec packets to traverse any NAT points on our network:
{% highlight Bash %}
set vpn ipsec nat-traversal enable
{% endhighlight %}

Setup the site to site connection with a pre shared secret:
{% highlight Bash %}
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn>
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> authentication mode pre-shared-secret
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> authentication pre-shared-secret <your_secret>
{% endhighlight %}

One side needs to initiate the connection, the other side needs to respond. We'll set this side to initiaite. Just make sure to set the other side to respond:
{% highlight Bash %}
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> connection-type initiate
{% endhighlight %}

Set ESP and IKE groups and IKEv2 reauth & local-address policy:
{% highlight Bash %}
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> default-esp-group ESP-1W
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> ike-group IKE-1W
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> ikev2-reauth inherit
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> local-address any
{% endhighlight %}

Finally, set up the tunnel:
{% highlight Bash %}
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> tunnel 1 local prefix <local_CIDR_range>
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> tunnel 1 remote prefix <remote_CIDR_range>
set vpn ipsec site-to-site peer <remote_host_ip_or_fqdn> tunnel 1 esp-group ESP-1W
{% endhighlight %}

You can also add more than one tunnel if you have more local or remote destinations. 

This needs to be done on both sides, while changing the appropriate values depending on what side you're on.

For debugging purposes, on the EdgeRouter Lite you can log in via SSH and run `sudo swanctl --log` as well as `show vpn log` which will output VPN logs and any errors. A few other commands that you could use to check on the status of the VPN are, `show vpn ipsec sa | state | status | policy`

I'll add another post for L2TP set up.
