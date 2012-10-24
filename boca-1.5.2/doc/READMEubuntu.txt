ICPC Linux installation script
----------------------------------
Copyright (c) 2009-2012 BOCA System (bocasystem@gmail.com) and C. P. de Campos.
Permission is granted to copy, distribute and/or modify this document
    under the terms of the GNU Free Documentation License, Version 1.3
    or any later version published by the Free Software Foundation;
    with no Invariant Sections, no Front-Cover Texts, and no Back-Cover Texts.
    A copy of the license is included in the section entitled "GNU
    Free Documentation License".
---------------------------------
File last modified: 24/aug/2012

==> The installv2.sh script is not necessary if you are using the virtual machine version. This is intended to those that
want to install the system as the host of a computer, or desire to create again the virtual machine version from the beginning.

The script installv2.sh available here was used to create the "ICPC Linux" image after a standard installation
of the ubuntu (or xubuntu) distribution. This is well-suitable in case you do not want to
use the virtualized version, for example, in the server of the contest. After using the installv2.sh script over a fresh
install of the ubuntu, you reach the exactly same content of the virtual machine version that is available. 
Take care with the following points during the installation of the ubuntu:
1) Use the name "icpcadmin" and password "icpcadmin" (no quotes obviously)  as the standard user during the installation.
2) Call the machine "icpc" (in the same screen where you set up the username above).
3) Use the simplest partitioning option (that is, everything in a single partition) and choose at least 10GB for it.

After installing ubuntu, the system reboots. Download the script installv2.sh to the home directory of icpcadmin,
make it executable by chmod 755 installv2.sh, and run it as root: 
$ sudo /bin/bash
# ./installv2.sh
(if you already updated packages, then run ./installv2.sh alreadydone)

and reboot the system to finish the configuration (such as setting passwords, IPs, etc).
Note this is going to give you a system just like you would get from the box (the virtual machine version). If you
want it to become a server, you must follow the same procedure that is indicated in the READMEvm.txt. This
mainly means that you need to run the script /etc/icpc/becomeserver.sh

The most recent installv2.sh script is available at http://www.ime.usp.br/~cassio/boca/ link named installv2.sh
In case of any questions, do not hesitate to contact me: bocasystem@gmail.com. Have fun!
