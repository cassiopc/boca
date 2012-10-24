/* Copyright 2002 Maratona de Programacao do IME-USP -- cef@ime.usp.br (written by S.G.Tavares)
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>

FILE *in, *out;

long mdc(long a, long b){
  long i, mdca=1;
  for(i=2; ((i<=a) && (i<=b)) ; i++)
			      if ((!(a%i)) && (!(b%i))) mdca = i;
  return mdca;
}


int main(){
  long x, y, i, j, k;
  char p1[8];
  double db1;
  int dh, dv;
  in = stdin;
  out = stdout;

  while(42){
    fscanf(in, "%ld%ld\n", &x, &y);
    if (!x || !y) break;
    fscanf(in, "%s\n", &p1[0]);
    if (p1[0]=='N') {
      dh = 0;
      dv = 0;
    }
    else if (p1[0]=='S') {
      dh = 1;
      dv = 1;
    }
    else if (p1[0]=='L') {
      dh = 1;
      dv = 0;
    }
    else {
      dh = 0;
      dv = 1;
    }
    for (i=x, j=y; ((!(i%2)) && (!(j%2))); i /= 2, j /= 2 );
    if (i%2) dh = !dh;
    if (j%2) dv = !dv;
    k = mdc(x, y);
    db1 = 100.0/(double)k;
    i = floor(100.0/(double)k);
    if (db1-(double)i > 0.5) i++;

    j = x/k + y/k -2;
    fprintf(out, "%3ld%%%10ld", i, j);
    if (dh){
      if (dv) fprintf(out, " Sul\n");
      else fprintf(out, " Leste\n");
    }
    else {
      if (dv) fprintf(out, " Oeste\n");
      else fprintf(out, " Norte\n");
    }
  }
                    
  return 0;
}
