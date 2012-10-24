/*
 safeexec
 Executar um comando num ambiente protegido
 pbv, 1999-2000
 alterado por cassio@ime.usp.br 2003-2011

 THIS PROGRAM HAS TO BE INSTALLED WITH SETUID ROOT TO HAVE ALL FUNCTIONS WORKING
 $ gcc -Wall -o safeexec safeexec.c
 $ chown root.root safeexec
 $ chmod 4555 safeexec
*/
#include <stdlib.h>
#include <fcntl.h>
#include <stdio.h>
#include <unistd.h>
#include <glob.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include <sys/resource.h>
#include <signal.h>
#include <time.h>

#include <string.h>
#include <errno.h>

/* tempo de erro entre o sinal e o estouro no caso de time-limit */
#define EPSILON 0.01

#define KBYTE 1024
#define MBYTE  (KBYTE*KBYTE)

pid_t child_pid;          /* pid of the child process */

double cpu_timeoutdouble = 5.0;
struct rlimit cpu_timeout = {5,5};    		/* max cpu time (seconds) */
struct rlimit max_nofile = {64,64};    		/* max number of open files */
struct rlimit max_fsize = {128*MBYTE,128*MBYTE};    /* max filesize */

struct rlimit max_data  = {128*MBYTE, 128*MBYTE};     /* max data segment size */
struct rlimit max_core  = {0, 0};                 /* max core file size */
struct rlimit max_rss   = {128*MBYTE, 128*MBYTE};     /* max resident set size */

struct rlimit max_processes = {64,64}; /* max number of processes */


int real_timeout = 30;                 /* max real time (seconds) */

int dochroot = 0, st=1;
int nruns = 1;
int allproc;
int killallproc;
int bequiet;
int checknchild;
int user, group;
const char vers[] = "1.5.1";

#define BUFFSIZE 256
char curdir[BUFFSIZE], rootdir[BUFFSIZE], saida[BUFFSIZE], entrada[BUFFSIZE], erro[BUFFSIZE];

struct Proc {
	int uid, pid, ppid, gid, group;
	unsigned long utime, stime;
	long cutime, cstime;
	unsigned long starttime;
	unsigned long vsize;
	long rss;
    long resident, share, text, lib, data;
};

unsigned int getprocs(pid_t ppid, pid_t ppid2, int userid, int groupid, int *nchild, struct Proc **Pr);

void exitandkill(int ret) {
	if(killallproc) {
		if(!bequiet)
			fprintf(stderr,"safeexec: killing all recent processes from this user/group to avoid possible malicious code... use -K if you don't want this\n");
		struct Proc *P;
		unsigned n;
		int nchild, i;
		if((n = getprocs(child_pid,getpid(),user,group,&nchild,&P)) > 0) {
			for(i = 0; i < n; i++) {
				if(!bequiet)
					fprintf(stderr,"safeexec: killing processes pid=%d\n", P[i].pid);
				kill(P[i].pid,9);   /* kill children and all processes with userid/groupid that started after us if instructed to do so */
			}		
		}
	}
	switch(ret) {
		case 8: fprintf(stderr,"safeexec: security threat because strange processes\n"); break;
		case 7: fprintf(stderr,"safeexec: memory limit exceeded\n"); break;
		case 5: fprintf(stderr,"safeexec: parameter problem\n"); break;
		case 6:
		case 4: fprintf(stderr,"safeexec: ERROR! internal error\n"); break;
		case 3: fprintf(stderr,"safeexec: time limit exceeded\n"); break;
		case 2: fprintf(stderr,"safeexec: runtime error\n"); break;
		case 9: fprintf(stderr,"safeexec: runtime error\n"); break;
	}
	exit(ret);
}

unsigned int getprocs(pid_t ppid, pid_t ppid2, int userid, int groupid, int *nchild, struct Proc **Pr) {
	const int SIZE=10000;
	char a[SIZE], b[SIZE];
	unsigned long pidtime=0;
	glob_t globbuf;
	unsigned int i, j, k, ki, oldi, n;
	FILE *tn;
	struct stat sstat;
	struct Proc *P, Ptmp;
  
	glob("/proc/[0-9]*", GLOB_NOSORT, NULL, &globbuf);
  
	P = calloc(globbuf.gl_pathc, sizeof(struct Proc));
	if (P == NULL) {
		if(!bequiet) fprintf(stderr,"safeexec: problem with malloc");
		exitandkill(4);
	}
	*Pr = P;
  
	for (i = j = 0; i < globbuf.gl_pathc; i++) {
		snprintf(a, sizeof(a)-1, "%s%s",
				 globbuf.gl_pathv[globbuf.gl_pathc - i - 1], "/stat");
		snprintf(b, sizeof(b)-1, "%s%s",
				 globbuf.gl_pathv[globbuf.gl_pathc - i - 1], "/statm");
#ifdef DEBUG
		fprintf(stderr,"[opening %s and %s]\n", a,b);
#endif
		tn = fopen(b, "r");
		if (tn == NULL) continue; /* process vanished since glob() */
		n = fread(b, 1, SIZE, tn);
		fclose(tn);
		tn = fopen(a, "r");
		if (tn == NULL) continue; /* process vanished since glob() */
		n = fread(a, 1, SIZE, tn);
		fstat(fileno(tn), &sstat);
		P[j].uid = sstat.st_uid;
		P[j].gid = sstat.st_gid;
		fclose(tn);

		sscanf(a, "%d %*s %*s %d %d %*s %*s %*s %*s %*s %*s %*s %*s %lu %lu %ld %ld %*s %*s %*s %*s %lu %lu %ld", 
			   &P[j].pid,&P[j].ppid,&P[j].group,&P[j].utime,&P[j].stime,&P[j].cutime,&P[j].cstime,&P[j].starttime,&P[j].vsize,&P[j].rss);
		sscanf(b, "%*s %ld %ld %ld %ld %ld", 
			   &P[j].resident,&P[j].share,&P[j].text,&P[j].lib,&P[j].data);
		if(P[j].pid == ppid2) pidtime = P[j].starttime;
#ifdef DEBUG
		fprintf(stderr,"[check %d,%d,%d,%ld]", P[j].pid, P[j].ppid, P[j].gid, P[j].rss);
#endif
		j++;
	}

	i = 0;
	oldi = 1;
	*nchild = 0;
	while(i != oldi) {
		oldi = i;
		for(k = i; k < j; ++k) {
			if(P[k].pid != ppid) {
				for(ki = 0; ki < i; ++ki)
					if(P[k].ppid == P[ki].pid) {
						break;
					}
				if(ki >= i) {
					continue;
				}
			}
			Ptmp = P[i];
			P[i] = P[k];
			P[k] = Ptmp;
			++i;
		}
	}
	*nchild = i;
	while(i < j) {
		if(P[i].uid != userid || P[i].gid != groupid || P[i].starttime < pidtime || P[i].pid==ppid2) {
			P[i] = P[--j];
		} else {
			if(!bequiet)
				fprintf(stderr,"safeexec: process %d has user %d, group %d, time %lu, which is suspicious\n",P[i].pid,userid,groupid,P[i].starttime);
			i++;
		}
	}
	globfree(&globbuf);
	return i;
}

int testsystem(pid_t p, pid_t pp, int userid, int groupid, double memlim, double rsslim) {
	struct rusage uso;
	getrusage(RUSAGE_CHILDREN, &uso);
	double mem1=0., mem2=0.;
	struct Proc *P;
	unsigned n, i;
	int ret = 1, nchild;
	n = getprocs(p,pp,userid,groupid,&nchild,&P);
	if(checknchild) {
		if(nchild != n) {
			if(!bequiet) {
				fprintf(stderr,"\nsafeexec: %d children of this user/group, but list has %d processes (who are the others?).\n",nchild,n);
				fprintf(stderr,"safeexec: detached children found! Aborting because of possible malicious code... use -a if you don't want this\n");
			}
			exitandkill(8);
		}
	}
	if(n > 0) {
		for(i = 0; i < n; i++) {
#ifdef DEBUG
			fprintf(stderr,"\ndata+stack:%ld, rss:%ld, exe:%ld\n", P[i].data,P[i].rss,P[i].lib+P[i].text+P[i].share); 
#endif
			mem2 += (P[i].lib+P[i].text+P[i].share+P[i].data)*getpagesize(); // in pages
			mem1 += P[i].rss*getpagesize();
		}
#ifdef DEBUG
		fprintf(stderr,"\nTOTAL rss:%lf, data+stack+exe:%lf\n", mem1, mem2); 
#endif
		if(mem2 > memlim || mem1 > rsslim) {
			if(!bequiet)
				fprintf(stderr,"\nsafeexec: memory limit exceeded\n");
			exitandkill(7);
		}
		if(nchild == 0) ret = 2;
	} else {
		mem1 = ((uso.ru_maxrss+uso.ru_ixrss+uso.ru_idrss+uso.ru_isrss));
//		fprintf(stderr,"WARNING: controlling only waited-for children! Security issues here if code is not trustful...\n");
		if(mem1 > memlim) {
			if(!bequiet)
				fprintf(stderr,"\nsafeexec: memory limit exceeded of %lfMB\n",memlim/1048576.);
			exitandkill(7);
		}
		ret = 0;
	}
	free(P);
	return ret;
}

char timekill=0;

/* alarm handler */
void handle_alarm(int sig) {
	static int iter=0;
#ifdef DEBUG
	fprintf(stderr,"parent alarmed\n");
#endif
	testsystem(child_pid,getpid(),allproc?user:-1,allproc?group:-1,max_data.rlim_max,max_rss.rlim_max);
	if(++iter >= real_timeout) {
		if(!bequiet)
			fprintf(stderr, "safeexec: timed-out (realtime) after %d seconds\n", real_timeout);
		fflush(stderr);
		timekill=1;
		kill(child_pid,9);   /* kill child */
		exitandkill(3);
	}
	alarm(1);   /* set alarm and wait for child execution */
}


void usage(int argc, char **argv) {
  fprintf(stderr, "safeexec version %s\nusage: %s [ options ] cmd [ arg1 arg2 ... ]\n", vers, argv[0]);
  fprintf(stderr, "available options are:\n");
  fprintf(stderr, "\t-c <max core file size> (default: %d)\n", 
	  (int) max_core.rlim_max);
  fprintf(stderr, "\t-f <max file size> (default: %d kbytes)\n", 
		  (int) (max_fsize.rlim_max/1024.));
  fprintf(stderr, "\t-F <max number of files> (default: %d)\n", 
	  (int) max_nofile.rlim_max);
  fprintf(stderr, "\t-d <max process memory> (default: %d kbytes)\n",
		  (int) (max_data.rlim_max/1024.));
  fprintf(stderr, "\t-m <max process resident memory> (default: %d kbytes)\n",
		  (int) (max_rss.rlim_max/1024.));
  fprintf(stderr, "\t-u <max number of child procs> (default: %d)\n",
	  (int) max_processes.rlim_max);
  fprintf(stderr, "\t-t <max cpu time> (default: %d secs)\n",
	  (int) cpu_timeout.rlim_max);
  fprintf(stderr, "\t-T <max real time> (default: %d secs)\n",
	  (int) real_timeout);
  fprintf(stderr, "\t-C <actual directory> (default: cwd)\n");
  fprintf(stderr, "\t-R <root directory> (default: none)\n");
  fprintf(stderr, "\t-n <chroot it?> (default: %d)\n", dochroot);
  fprintf(stderr, "\t-r <number of runs?> (default: %d)\n", nruns);
  fprintf(stderr, "\t-i <standard input file> (default: not defined)\n");
  fprintf(stderr, "\t-o <standard output file> (default: not defined)\n");
  fprintf(stderr, "\t-e <standard error file> (default: not defined)\n");
  fprintf(stderr, "\t-U <user id> (default: %d)\n", user);
  fprintf(stderr, "\t-G <group id> (default: %d)\n", group);
  fprintf(stderr, "\t-p <show spent time?> (default: %d)\n", st);
  fprintf(stderr, "\t-a disable checking all processes (look only to the non-detached children) (default %s)\n", allproc?"enabled":"disabled");
  fprintf(stderr, "\t-s disable checking existence of detached children (default %s)\n", checknchild?"enabled":"disabled");
  fprintf(stderr, "\t-K disable killing all processes with user/group id after us (default %s)\n", killallproc?"enabled":"disabled");
  fprintf(stderr, "\t-q be quiet (default %s)\n", bequiet?"enabled":"disabled");
/*******
Note that currently Linux does not support strong memory usage limits, so we have
to enforce it by checking the amount of memory periodically
********/
}

int main(int argc, char **argv) { 
  int status, opt, ret=0;
  time_t ini;
  int currun = 0;
  struct stat sstat;
  double dt;
  setvbuf(stderr, NULL, _IONBF, 0);
  entrada[0] = saida[0] = erro[0] = rootdir[0] = curdir[0] = 0;
  user = group = -1;
  allproc = 1;
  checknchild=1;
  killallproc=1;
  bequiet=0;

  if(argc>1 && !strcmp("--help", argv[1])) {
    usage(argc,argv);
    exit(5);
  }

  /* parse command-line options */
  getcwd(curdir, BUFFSIZE);  /* default: use cwd as rootdir */
  while( (opt=getopt(argc,argv,"qKar:c:d:m:f:F:t:T:u:n:i:o:e:C:R:G:U:p:s")) != -1 ) {
    switch(opt) {
		case 'q': bequiet=1;
			break;
		case 'a': allproc=0;
			break;
		case 'K': killallproc=0;
			break;
		case 's':
			checknchild=0;
			break;
    case 'c': max_core.rlim_max = max_core.rlim_cur = atoi(optarg);
      break;
    case 'f': max_fsize.rlim_max = max_fsize.rlim_cur = KBYTE*atoi(optarg);
      break;
    case 'F': max_nofile.rlim_max = max_nofile.rlim_cur = atoi(optarg);
      break;
    case 'd': max_data.rlim_max = max_data.rlim_cur = KBYTE*atoi(optarg);
      break;
    case 'm': max_rss.rlim_max = max_rss.rlim_cur = KBYTE*atoi(optarg);
      break;
		case 't': cpu_timeout.rlim_max = cpu_timeout.rlim_cur = 1 + ((int) atof(optarg));
		cpu_timeoutdouble = atof(optarg);
      break;
    case 'T': real_timeout = atoi(optarg);
      break;
    case 'u': max_processes.rlim_max = max_processes.rlim_cur = atoi(optarg);
      break;
    case 'U': user = atoi(optarg);
      break;
    case 'r': nruns = atoi(optarg);
      break;
    case 'G': group = atoi(optarg);
      break;
    case 'R': strncpy(rootdir, optarg, 255);  /* root directory */
              rootdir[255]=0;
      break;
	case 'C': strncpy(curdir, optarg, 255);  /* root directory */
              curdir[255]=0;
      break;
    case 'i': strncpy(entrada, optarg, 255);
              entrada[255]=0;
      break;
    case 'o': strncpy(saida, optarg, 255);
              saida[255]=0;
      break;
    case 'e': strncpy(erro, optarg, 255);
              erro[255]=0;
      break;
    case 'n': dochroot = atoi(optarg);
      break;
    case 'p': st = atoi(optarg);
      break;
    case '?': usage(argc,argv);
      exit(5);
    }
  }

  if(optind >= argc) {  /* no more arguments */
    usage(argc,argv);
    exit(5);
  }

  if(dochroot && chroot(rootdir)) { 
	  fprintf(stderr,"ERROR %s\n",strerror(errno));
	  fprintf(stderr,"%s: unable to chroot to directory %s\n",
			  argv[0], rootdir); 
    exit(4); 
  } 
  /* change the root directory (ZP: and working dir, in not root)*/
  if(curdir[0] && chdir(curdir)) { 
	  fprintf(stderr,"ERROR %s\n",strerror(errno));
	  fprintf(stderr,"%s: unable to change directory to %s\n",				  
			  argv[0], curdir); 
    exit(4); 
  } 

  stat(".", &sstat);
  if(user == -1) {
    user = (int) sstat.st_uid;
	  if(!bequiet) {
		  fprintf(stderr, "Security note: if the sub-code is not trustful, it shall be run with a different user from the one running safeexec.\
Use -U and -G for that, but you might need to have root privilegies.\n");
	  }
  }
  if(group == -1)
    group = (int) sstat.st_gid;
  if(user == 0 || group == 0) {
	  fprintf(stderr, "ERROR: safeexec shall not be instructed to run the sub-code as root\n");
	  exit(4);
  }

  time(&ini);
  dt = 0.;
//  int iter=0;
 doagain:
  if((child_pid=fork())) { 
    struct rusage uso;
    /* ------------------- parent process ----------------------------- */
	if(currun == 0) {
		setrlimit(RLIMIT_CORE, &max_core);
	}
    currun++;
	if(!bequiet)
		fprintf(stderr,"safeexec: starting the job. Parent controller has pid %d, child is %d...\n",getpid(),child_pid);
	alarm(1);   /* set alarm and wait for child execution */
	signal(SIGALRM, handle_alarm);
	while(waitpid(child_pid, &status, 0) != child_pid) ;
	if(timekill) exitandkill(3);

	testsystem(child_pid,getpid(),allproc?user:-1,allproc?group:-1,max_data.rlim_max,max_rss.rlim_max);

    getrusage(RUSAGE_CHILDREN, &uso);
    dt = uso.ru_utime.tv_sec+(double)uso.ru_utime.tv_usec/1000000.0+
      uso.ru_stime.tv_sec+(double)uso.ru_stime.tv_usec/1000000.0;
//    printf("user runnning time: %.4lf\n",uso.ru_utime.tv_sec+(double)uso.ru_utime.tv_usec/1000000.0); 
//    printf("system runnning time: %.4lf\n",uso.ru_stime.tv_sec+(double)uso.ru_stime.tv_usec/1000000.0); 
//    printf("total runnning time: %.4lf\n",dt); 

    if (dt >= cpu_timeoutdouble) {
//      printf ("utsec=%d utusec=%d stsec=%d stusec=%d\n", uso.ru_utime.tv_sec, uso.ru_utime.tv_usec, uso.ru_stime.tv_sec, uso.ru_stime.tv_usec);
		if(!bequiet)
			fprintf(stderr, "safeexec: timed-out (cputime) after %.2lf seconds\n", cpu_timeoutdouble);
		fflush(stderr);
//      fprintf(stdout, "timed-out (cputime) after %d seconds\n", cpu_timeoutint);
//      fflush(stdout);
      exitandkill(3);
    }

    // check if child got an uncaught signal error & reproduce it in parent
    if(WIFSIGNALED(status))  {
		fprintf (stderr, "safeexec: RUN-TIME SIGNAL REPORTED BY THE PROGRAM %s: %d\n", argv[optind], WTERMSIG(status));
      fflush(stderr);
//      raise(WTERMSIG(status));
      exitandkill(2);
    }

    if(WIFEXITED(status)) {
		if(WEXITSTATUS(status)) {
			ret = WEXITSTATUS(status)+10;
			if(!bequiet) 
				fprintf (stderr, "safeexec: PROGRAM EXITED WITH NONZERO CODE %s: %d\n",
						 argv[optind], ret-10);
		} else ret = 0;
    } else {
		fprintf (stderr, "safeexec: PROGRAM TERMINATED ABNORMALLY %s\n",
				 argv[optind]);
		exitandkill(9);
    }

    if(currun < nruns) goto doagain;

    // otherwise just report the exit code:
    if (st) fprintf (stderr, "safeexec: TOTAL TIME RUNNING %s: %u sec (%lf sec)\n", argv[optind], (unsigned int) (time(NULL)-ini), dt);
    exitandkill(ret);
  } else {
    /* ------------------- child process ------------------------------ */
#ifdef DEBUG
	  fprintf(stderr,"child started\n");
#endif
	  /* change the group id to 'nobody' */
	  if(setgid(group)<0) {
		  fprintf(stderr,"ERROR %s\n",strerror(errno));
		  fprintf(stderr, "%s: unable to change gid to %d\n", argv[0], group);
		  exit(4);
	  }
	  /* change the user id to 'nobody' */
	  if(setuid(user)<0) {
		  fprintf(stderr,"ERROR %s\n",strerror(errno));
		  fprintf(stderr, "%s: unable to change uid to %d\n", argv[0], user);
		  exit(4);
	  }
  
    if(currun==nruns-1) {
    if (saida[0]) freopen(saida, "w", stdout);
    if (erro[0]) freopen(erro, "w", stderr);
    } else {
      freopen("/dev/null", "w", stdout);
      freopen("/dev/null", "w", stderr);
    }
    if (entrada[0]) freopen(entrada, "r", stdin);

    /* attempt to change the hard limits */
    /*******Note that currently Linux does not support memory usage limits********/

	cpu_timeout.rlim_max+=1; cpu_timeout.rlim_cur+=1;

    if( setrlimit(RLIMIT_CPU, &cpu_timeout) || 
	setrlimit(RLIMIT_DATA, &max_data) ||
	setrlimit(RLIMIT_STACK, &max_data) ||
	setrlimit(RLIMIT_CORE, &max_core) ||
	setrlimit(RLIMIT_RSS, &max_rss) ||
	setrlimit(RLIMIT_FSIZE, &max_fsize) ||
	setrlimit(RLIMIT_NOFILE, &max_nofile) ||
	setrlimit(RLIMIT_NPROC, &max_processes) ) {
      fprintf(stderr,"ERROR %s\n",strerror(errno));
      fprintf(stderr, "%s: can't set hard limits\n", argv[0]);
      exit(6);
    }

    /* attempt to exec the child process */
    if(execv(argv[optind],&argv[optind]) < 0) {
      fprintf(stderr,"ERROR %s\n",strerror(errno));
      fprintf(stderr, "%s: unable to exec %s\n", argv[0], argv[optind]);
      exit(6);
    } 
  }
  return 0;
}
