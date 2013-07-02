/* this file illustrate a bug in diff that, even using -w -B -b,
 * it will think that the files are different and will return
 * exit code 1, when the only difference between them is a line
 * in the end with a single white space.
 * Author: cassio@ime.usp.br
 * Last updated: 18/aug/2008
 */
#include <stdio.h>
int main(void) {
  int i, j, n, max, inst=0;
  while(42) {
    scanf("%d", &n);
    if(!n) break;
    max=1;
    for(i=0; i<n; i++) {
      scanf("%d", &j);
      if(j>max) max=j;
    }
    for(i=0; max>0; i++)
      max >>= 1;
    printf("Instancia %d\n%d\n\n", ++inst, i*n);
  }
  printf(" \n");
  return 0;
}
