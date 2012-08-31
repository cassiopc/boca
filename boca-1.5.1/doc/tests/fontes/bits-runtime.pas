program bits;
var i, inst, max, n: Longint;
    v: array[0..1] of Longint;
begin
  while true do
  begin
    read(n);
    if n=0 then break;
    max := 1;
    for i:=0 to n-1 do
    begin
      read(v[i]);
      if v[i]>max then max:=v[i]
    end;
    i:=0;
    while max>0 do
    begin
      max := max div 2;
      i := i + 1
    end;
    write('Instancia ');
    inst := inst + 1;
    writeln(inst);
    writeln(i*n);
    writeln()
  end
end.
