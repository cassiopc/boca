import java.io.*;
class bits {
  public static void main(String args[]) throws Exception {
    StreamTokenizer st = new StreamTokenizer(System.in);
    int i, j, n, max, inst=0;
    while(42==42) {
      st.nextToken();
      n = (int) st.nval;
      if(n==0) break;
      max=1;
      for(i=0; i<n; i++) {
        st.nextToken();
        j = (int) st.nval;
        if(j>max) max=j;
      }
      for(i=0; max>0; i++)
        max >>= 1;
      inst++;
      System.out.println("Instancia " + inst);
      System.out.println(i*n + "\n");
      throw new Exception("OPA! Runtime error, hehehe...");
    }
  }
}
