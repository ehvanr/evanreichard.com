---
layout: post
title:  "Python Dummy SMTP Server as a Windows Service"
description: Debugging SMTP Windows Service writen in Python
permalink: /python-dummy-smtp-server-as-a-windows-service
---

The other day, for debugging purposes, I needed to deploy a dummy SMTP server that would receive, log and NOT send emails. There’s actually a cool little Python one-liner that does something similar to this:

{% highlight Bash %}
python -m smtpd -n -c DebuggingServer 0.0.0.0:25
{% endhighlight %}

Bam. A basic SMTP server that will output messages to stdout. Note that since it’s a port less than 1024, we need to run this command as sudo or in an administrative command prompt in order to bind to port 25.

Before I continue, I wan’t to make you aware that I’ve never scripted in Python before. This was my first application (Yeah, screw you, “Hello World”) So I lack experience, and some of my solutions may be inefficient and bad practice. (Please let me know if so!)

For my application, the downside of the previous command is that it only outputs to stdout and does not run as a service (I was looking for more of a permanent solution for debugging / dev purposes…  a lot of debugging.)

As a “solution” I wrapped a derivative of this command into a Windows Service using a Python extension called [pywin32][1].

<!--excerpt_separator-->

Here's what I came up with:

{% highlight Python %}
import win32serviceutil
import win32service
import win32event

import servicemanager
import threading
import asyncore
import smtpd
import time
import sys
import os

class AppServerSvc (win32serviceutil.ServiceFramework):
    _svc_name_ = "SMTPDummyServer"
    _svc_display_name_ = "SMTP Dummy Server"

    def __init__(self,args):
        win32serviceutil.ServiceFramework.__init__(self,args)
        self.hWaitStop = win32event.CreateEvent(None,0,0,None)

    def SvcStop(self):
        self.ReportServiceStatus(win32service.SERVICE_STOP_PENDING)
        win32event.SetEvent(self.hWaitStop)

    def SvcDoRun(self):
        self.ReportServiceStatus(win32service.SERVICE_RUNNING)

        if not os.path.exists("C:\\DummySMTPLogs\\"):
        os.makedirs("C:\\DummySMTPLogs\\")

        server = smtpd.DebuggingServer(('0.0.0.0', 25), None)
        asyncoreThread = threading.Thread(target=asyncore.loop,kwargs = {'timeout':1})
        asyncoreThread.start()
        myStatusThread = threading.Thread(target=win32event.WaitForSingleObject, args=(self.hWaitStop, win32event.INFINITE))
        myStatusThread.start()

        while True:
            if myStatusThread.isAlive():
                fileName = time.strftime("%Y%m%d")
                completePath = os.path.abspath("C:\DummySMTPLogs\%s.log" % fileName)
                sys.stdout = open(completePath, 'a')
            else:
                self.server.close()
                self.asyncoreThread.join()
                break

            time.sleep(1)

    if __name__ == '__main__':
        win32serviceutil.HandleCommandLine(AppServerSvc)

{% endhighlight %}

In order to install this service, you’d execute:

{% highlight Bash %}
python DummySMTP.py install
{% endhighlight %}

Now if you go to services, you can see that there’s a new service called “SMTP Dummy Server” (As specified in line 15) You can now start and stop this service at will and it will act as an SMTP server that will save all emails to the `C:\DummySMTP\` folder with daily rotating log files.

## Issues Encountered

One issue was that in order for the server to keep polling for incoming messages, you need to execute `asyncore.loop()` – The problem is that when executed, it locks and we can no longer do anything. Unfortunately there is no “clean” way to quit out of this. (So what do we do if the user tells the service to stop?) One way to have it end naturally is to close all open channels as per the [documentation][2].

The service also needs to wait for a stop command. This is done through this:
{% highlight Python %}
win32event.WaitForSingleObject(self.hWaitStop, win32event.INFINITE)
{% endhighlight %}

Which waits until a stop event. (Or any event change, I believe) Meaning: yay, more threads!

On top of this, I wanted to log to file so I needed to have a loop that redirected stdout to file. Those two issues were solved like so:

{% highlight Python %}
asyncoreThread = threading.Thread(target=asyncore.loop,kwargs = {'timeout':1})
asyncoreThread.start()
myStatusThread = threading.Thread(target=win32event.WaitForSingleObject, args=(self.hWaitStop, win32event.INFINITE))
myStatusThread.start()
{% endhighlight %}

Now we’re free to execute our logging commands :smile:


{% highlight Python %}
while True:
    if myStatusThread.isAlive():
        fileName = time.strftime("%Y%m%d")
        completePath = os.path.abspath("C:\DummySMTPLogs\%s.log" % fileName)
        sys.stdout = open(completePath, 'a')
    else:
        self.server.close()
        self.asyncoreThread.join()
        break

    time.sleep(1)
{% endhighlight %}

This just keeps looping until the myStatusThread is no longer alive. Once no longer alive, the smtpd server is closed and the asyncoreThread is joined.  The asyncoreThread should naturally close due to us closing all open channels and the Windows Service reports a clean service stop.


[1]: https://sourceforge.net/projects/pywin32/
[2]: https://docs.python.org/2/library/asyncore.html
