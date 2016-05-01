---
layout: post
title:  "Right Click MD5 or SHA1 Checksum!?"
description: Right click on the file to get the MD5 or SHA1 checksum
permalink: /right-click-checksum/
---

Wouldn’t it be nice to right click on a file and calculate the MD5 or SHA-1 checksum?  That’s what I wanted to do and this is how I did it!

Download the [Microsoft File Checksum Integrity Verifier (FCIV)][1] and extract to where ever.  It will have a file called `fciv.exe` that you should copy to `C:\Windows\System32\` (You can alternatively add an additional path parameter to the system environmental variables that points to the directory that contains fciv.exe)

<!--excerpt_separator-->

Then open `regedit` and add the appropriate keys:

![Checksum1](/assets/images/posts/2013-11-10-checksum1.png)

Now right click any file and there will be a “Checksum” option that you can select and it will automatically calculate the MD5 and SHA-1 checksum :smile:

![Checksum2](/assets/images/posts/2013-11-10-checksum2.png)

![Checksum3](/assets/images/posts/2013-11-10-checksum3.png)
