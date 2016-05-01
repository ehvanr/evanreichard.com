---
layout: post
title:  "Wrapping a Python Windows Service into an EXE Using py2exe"
description: Convert a python windows service into on self containing exe.
permalink: /wrapping-a-python-windows-service-into-an-exe-using-py2exe
---

[After writing a debugging / dev SMTP server as a Windows Service in Python using pywin32][1], I decided to figure out how to wrap the Python Windows Service into an EXE so you wouldn’t have to install Python to get it running.

I had first attempted to do this in PyInstaller but upon starting the service, Windows would report failure, but the server would still be working. I gave up on PyInstaller and found a solution in py2exe.
<!--excerpt_separator-->

This was my py2exe `setup.py`:

{% highlight Python %}
from distutils.core import setup
import py2exe

setup(
    service = ["DummySMTP"],
    description = "A dummy SMTP server that logs to file.",
    modules = ["SMTPDummyServer"],
    cmdline_style='pywin32',
)
{% endhighlight %}

Where “DummySMTP” refers to the `DummySMTP.py` file in the current working directory.

To compile the EXE, run:

{% highlight Bash %}
python setup.py py2exe
{% endhighlight %}

This will create two folders in the current working directory called `dist` and `build`.  In the `dist` directory will be your EXE and other necessary files.

To install this as a service, in an administrative command prompt, run:

{% highlight Bash %}
DummySMTP.exe -install
{% endhighlight %}

Please note that when the service runs, it will access that directory.  So relocate the directory accordingly.

That's it!

[1]: ./python-dummy-smtp-server-as-a-windows-service
