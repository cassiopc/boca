READMEvm.txt file of the ICPC Linux VM (release 2012)
=================================
Copyright (c) 2009-2012 BOCA System (bocasystem@gmail.com) and C. P. de Campos.
Permission is granted to copy, distribute and/or modify this document
    under the terms of the GNU Free Documentation License, Version 1.3
    or any later version published by the Free Software Foundation;
    with no Invariant Sections, no Front-Cover Texts, and no Back-Cover Texts.
    A copy of the license is included in the section entitled "GNU
    Free Documentation License".
=================================
Last modified: 24/aug/2012

This file concerns the ICPC Linux image available for virtualbox or vmware. It was created as a 
vmware-type hard-disk image with a system built up over a ubuntu (or xubuntu) distribution. This 
README is also relevant if you have used the installv2.sh script to build the server natively, as the
system will have the exactly same characteristics of the VM version mentioned here.

- You need the vmplayer from www.vmware.com to use this image. As far as I know, vmplayer is
free-of-charge for linux and windows hosts. Check it. You can also use virtualbox, which
is free and works pretty well too.

- Internet access is restricted inside the box. By default, users can only access 
bombonera.ime.usp.br and bombonera.org, servers where BOCA is hosted. During the first boot time, 
you are able to choose a different IP address of your server instead of bombonera. If you are going to 
run a contest, you must do that! The system inside the virtual machine (or just VM) will
only be able to access such server and nothing else. An alias with the name boca is created
for such IP, so it is possible to connect to it using the name boca instead of typing the
IP address. So, during the first initialization of each VM you should configure the IP address
of your BOCA server. The image provided here can be used as a BOCA server too. See next bullets
on how to do that. In the team machines, check if the internet is really blocked. If not, the
initialization scripts might be malfunctioning, or the computer might be mistakenly set to be
a server (this can be easily checked by verifying whether the file /etc/icpc/.isserver exists or not).

- There are two linux users in the box: icpcadmin, which has right to become root using
sudo, and the user icpc. The latter has password icpc, and more restricted access. The user
icpc is intended to be used by the teams, judges, staff, or anyone else. The icpcadmin is 
an administrative account and the password must be known only by the sysadmin, director of the
contest, and so on. During the first boot time, you are able to change the password of the icpcadmin
account. DO IT and keep it safe! The default password is icpcadmin. Note that such
users (icpcadmin and icpc) have nothing to do with users of the BOCA web system. Each
team must have a distinct user to login on BOCA, which are configured through the BOCA web interface.
Still, each team will logon into the linux box using the same user: icpc. As mentioned, this is
not a security problem because the user icpc is just a local account in the linux system without
any privileges.

- To run a contest using a virtual machine, you probably need at least 384MB of RAM inside a 
"good" computer (then you can configure the VM to run with 256MB). I have tested the vm image
configured to use 512MB. If your host has for example 1GB, you may change the vmplayer 
to use 768MB, although 512 should be fine too. Just keep some room for the underlying operating 
system. Using more memory inside the vm is better for the teams, so they can run heavier tools.

- Unzipped, the image takes around 12 GB of your hard disk. You need this space in the hard
disk of each computer. If this is a problem for you, it is possible to rebuild the vm system
with less space (8GB for clients is enough, the server has to have more though).

- It is possible to maximize the VM window so as it uses the whole screen area. The current
image is set to 800x600 or 1024x768 pixels, but that can probably be increased depending on your 
video card and monitor settings. The keyboard config can also be changed
inside the system configurations of ubuntu.

- During the first startup, an script will ask you for the BOCA database password (after 
asking the BOCA server IP address and password of the icpcadmin). YOU MUST ONLY FILL
SUCH PASSWORD IN THERE IF the machine being configured is NOT going to be used by a team. With
this password, it will be possible to access the database directly (instead of by web). Teams
shall access the system only by using the web interface. This password is intended to be used
in the machine that will have the autojudging system, as the autojudging script needs to 
connect to the database directly (see next item about the autojudge). In fact only the
autojudging machine (or machines, if you have more than one) need this password to be set.
SO PAY ATTENTION TO ONLY SET THIS PASSWORD IN THE AUTOJUDGING MACHINES.

- An autojugding machine is used to automatically compile and execute the codes submitted
by the teams. DO NOT run it on the same computer as the BOCA server (the web server and the database
server). Because teams may submit malicious code, it is only safe to run it on a separate computer.
The worst scenario (in case a team's code hangs the computer) will require to reboot such 
computer. About the configuration, the only difference between the autojudge computer and 
team's computers is the setup of the database password, as mentioned before. You must set up
the database password during the initialization of the autojudging computer. Then, to run the 
autojudging system, you need to login as icpcadmin (privileged user), open a command-line 
terminal (it is inside accessories), and run "sudo /var/www/boca/tools/autojudge.sh" (without quotes).
If everything is fine, some dots will appear on the screen while the script runs an infinite
loop waiting for submissions. Ctrl-C stops the autojudge. The autojudging shall be started 
after configuring the server to run the contest, otherwise it will eventually output an error or
freeze. If you see the dots happening every few seconds, you are in the correct track. The dots mean
the autojudging is running, but there are no submissions to process.

- With old versions of ICPC Linux, all the team files were kept in the server. Now this is
NOT true anymore. Everything is stored locally. So, after a warmup and before the real
contest, it is necessary to go to each team computer, open a command-line shell (you do not
need to change to the privileged user, the unprivilegied icpc user is enough) and run 
the command
/etc/icpc/cleandisk.sh
After that, reboot the system inside the virtual machine (not the host computer itself, only the VM!) 
and you are done. All files belonging to the icpc user have been erased.

- Because the files are stored in the local team computers, now BOCA (by web) has an option
to make file backups, where teams can save their files on the server. Currently this must be done 
manually by the teams (using the web interface of BOCA). There is a script available inside the
ICPC linux (and inside the BOCA package) that teams may use to backup their files. To
do that, they can just run "makebkp.sh". If they want to save other files, it is possible to use 
the web interface of the teams. Note that teams should submit backup files with small size only, but 
that is not a problem because source codes are really small. The script makebkp.sh is available 
(but not very tested) which sends files .c, .cpp, .java and .in (it looks for these files in the 
current directory) and send them to the server. IT IS IMPORTANT TO LET THE TEAMS KNOW ABOUT THIS 
SITUATION AND THE POSSIBILITY OF SAVING FILES TO THE SERVER, EITHER BY USING THE makebkp.sh IN 
THE COMMAND-LINE, OR BY DIRECTLY UPLOADING THE FILES IN THE WEB INTERFACE.

- The most complicated thing to run the contest using a VM is that you need to restrict the
use of the host operating system. For example, it would be possible for a team to switch
the window (from the VM to another program), and then use the internet (in case you have
internet available in the host, which is usually true). I believe that the simplest approach
to restrict the system is to impose a set of packet filtering rules in the host system, using
a firewall solution. That would make the host system useless for the team. The only permission
that the host must have is to allow connections to the BOCA server and (possibly) to the 
authentication server of your network (in case it is needed to keep the host running. Better 
if that can be avoided too). If your host is linux, then it is easy: you just need to include some 
rules in the iptables (certainly a sysadmin knows how to do it).
For windows (which is probably more usual and easy to deal with), there are many free-of-charge
solutions. I do not know them well, but let me cite some: www.r-firewall.com,
www.personalfirewall.comodo.com, tiny personal firewall (it is possible to find it online),
perhaps the windows firewall can be enough (in some windows versions). NOW THE MOST COMMON /
PRACTICAL WAY TO SOLVE THE PROBLEM: keep an eye on the teams all the time. Have some volunteers 
looking the teams work on the computers during the whole contest. No one is allowed to leave the 
VM and use the underlying system. If they do that, then simply expel them from the contest! 
Give a warn of the possible penalty to everyone before starting and that is it. Quick and clean :D

- Any ICPC Linux VM can become a server for the contest, which in this case will be a BOCA server running
inside a virtual machine system. To do that, you just need to login as user
icpcadmin, become root using the command "sudo /bin/bash"
and execute the script /etc/icpc/becomeserver.sh
READ THE FULL TEXT BEFORE RUNNING THIS SCRIPT. THE VM, TO BECOME A SERVER, MUST BE SET TO BRIDGE 
MODE (see explanation below). At this moment, you will be prompted to define the DB password or use a 
random one. After that, the IP address of this computer must be used to configure all other VMs (during those
startup questions). 
Some considerations about a server: more memory must be used for the server. Have a computer
with enough memory and increase the memory of the VM. Also take care with the
disk space. The current image has only a few gigabytes free (still, that must be more than enough). 
Finally, note that to run the VM as the server, it must be configured to run in BRIDGE mode 
with respect to the network interface, otherwise it will not be possible for other computers 
to reach it. BRIDGE mode means that you must look in your vmplayer (whatever player you are using)
to configure the network as in BRIDGE mode. You may need to reboot the VM. If after rebooting it
and running the command-line program "ifconfig" you can see that the IP address of your computer
is the same inside the VM and outside, you are done. Besides that, the underlying (host) operating 
system must have NO apache or postgresql running, nor
any other TCP server in the ports 80 (http), 443 (https) and 5432 (postgres). Otherwise this will create a
conflict when running the BOCA server in the VM with BRIDGE mode.
Yet I point out that running the BOCA system in a server computer (without the VM) 
is the mostly tested solution, but the VM version was used in many sites already. If you want to 
run it natively, stop here, get a ubuntu 12.04, install it, and run the installv2.sh script over it. See the
file READMEubuntu.txt for more details. You will need a computer that you can format and reinstall.
That is the simplest way of having a full ICPC linux server
running apart from using the VM version itself, as explained here.

- After having the server and clients running up, it is necessary to configure the BOCA
web system. For that purpose, please follow the steps of the ADMIN.txt file in the doc/
folder of the boca system. It is available in the BOCA package or inside the ICPC
linux at /var/www/boca/doc/. There are some examples inside the subfolders there.

- Try to use the vm without fear. In the worst case, download it again and start again
(or even better, keep a copy of the image without changes). Any questions, please
do not hesitate to contact me: bocasystem@gmail.com.

- If you need to update the BOCA system that is running in your server (with the risk of losing
data in the database of BOCA!), then you may run the script /etc/icpc/installboca.sh
If you need to update general scripts that are used in the system and were installed by the
installv2.sh procedure, you may run the script /etc/icpc/installscripts.sh
These are easy ways to update the system in case bugs are found and fixed, without having to download
again the whole VM image. Good luck and have fun!
