---
layout: post
title:  "Enable Nested Virtualization in ESXi Without the Web Client"
description: Enable Nested Virtualization in ESXi Without the Web Client
permalink: /enable-nested-virtualization-in-esxi-without-web-client
---

**Note:** This information is outdated.  There is now a HTML5 Web Client for ESXi standalone. However, there are some bugs so this may still be applicable. [See here.][1]

So I decided to start studying for the RHCSA/RHCE exam and wanted to set a test lab up.  I currently have an ESXi server that hosts various VM’s for stuff I do back home – I manage it remotely from school.

With the RHCSA exam, I needed to be able to deploy KVM’s.  This requires Intel’s VMX CPU flag.  The issue is that by default, the VM’s on ESXi will not have VMX capability. There’s something called nested virtualization that became “unofficially” supported in ESXi 5.0, and officially supported in ESXi 5.1+

Unfortunately, if you run the free version of ESXi, you’re unable to enable nested virtualization as it’s only able to be enabled via the web client as opposed to the C# vSphere client… As far as I know, you’re not able to get the Web Client without vCenter, a paid product.

<!--excerpt_separator-->

To enable nexsted virtualization manually, you need VM version 8 or 9 (9 is the max you can have before being unable to modify settings in the C# client).

![Virtualization1](/assets/images/posts/2013-10-28-virtualization1.png)

Then you need to edit your VM's `.vmx` file:

![Virtualization2](/assets/images/posts/2013-10-28-virtualization2.png)

And add the following line:

![Virtualization3](/assets/images/posts/2013-10-28-virtualization3.png)

Make sure to backup your previous `.vmx` file just in case.  When you upload the modified file, make sure that it has `rwxr-xr-x` permissions (chmod 0755)

Boot up the VM, and check the CPU flags by executing

{% highlight Bash %}
cat /proc/cpuinfo
{% endhighlight %}

![Virtualization4](/assets/images/posts/2013-10-28-virtualization4.png)

And there's the `vmx` flag :smile:

It also appears that unless you have the `ept` CPU flag, you’ll only be able to run x86 nested VM’s.

[1]: https://labs.vmware.com/flings/esxi-embedded-host-client
