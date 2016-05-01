---
layout: post
title:  "Custom Resolution in Ubuntu 13.10 on Microsoft HyperV"
description: Enable the ability to set a custom resolution in Ubuntu while running on HyperV
permalink: /ubuntu-custom-resolution-hyperv
---

By default you're unable to set a custom resolution in Ubuntu 13.10 while running on HyperV.  This is how I set it up. 

Modify the following file and make sure that you include the listed modules:


{% highlight Bash %}
sudo vim /etc/initramfs-tools/modules
{% endhighlight %}

<!--excerpt_separator-->

![HyperV1](/assets/images/posts/2014-02-23-hyperv1.png)

Update the initramfs:

{% highlight Bash %}
sudo update-initramfs -u
{% endhighlight %}

Install the linux-image-extra-virtual package:

{% highlight Bash %}
sudo apt-get install linux-image-extra-virtual
{% endhighlight %}

Update the `GRUB_CMDLINE_LINUX_DEFAULT` line in the GRUB file to reflect your desired screen resolution:

{% highlight Bash %}
sudo vim /etc/default/grub
{% endhighlight %}

![HyperV2](/assets/images/posts/2014-02-23-hyperv2.png)

You can see that I changed from `GRUB_CMDLINE_LINUX_DEFAULT="quiet splash"` to `GRUB_CMDLINE_LINUX_DEFAULT="quiet splash video=hyperv_fb:1920x1080"`

Update GRUB:

{% highlight Bash %}
sudo update-grub
{% endhighlight %}

And now reboot and the changes should take effect :smile:

{% highlight Bash %}
sudo reboot
{% endhighlight %}
