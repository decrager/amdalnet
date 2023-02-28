
1. Meeting rutin
2. quick win
a. static html
  - sebanyak-banyaknya ada, teh husnul
  - generator --> bung
  - form form
b. web gis
c. collaborative
  - diakses 100
  - 


RKA
RKL/RPL

UKL/UPL
- template puluhan
- 
- matriks --> free teks
- 
AMDAL

- struktur
- isian parsial --> narasi
- dokumen
- isian data
- import / export
  - templating
- integrasi
- collaborative
  - comment
  - theme
- 

Onlyoffice:
- user integration -> ok
- comment -> ok
- review -> ok
- langguage: id -> not ok
- custom toolbar -> build source
- protect -> still explore
- templating -> still explore
  - import 
  - variabels
  * candidate, tech:
  - docbuilder
  - phpoffice
- Export

## Command To Run OODS

```
sudo docker run -i -t -d -p 9000:80 --restart=always --name oods \
    -e JWT_ENABLED=false \
    -e JWT_SECRET=LIo3a2QLrCQ7BPi8ET09 \
    -v /home/ubuntu/office/DocumentServer/data:/var/www/onlyoffice/Data \
    -v /home/ubuntu/office/DocumentServer/logs:/var/log/onlyoffice \
    -v /home/ubuntu/office/DocumentServer/etc/default.json:/etc/onlyoffice/documentserver/default.json \
    onlyoffice/documentserver:7.2
```

http://oo.mymac.net/doceditor.php?fileID=61943e88ad99a.docx&user=&action=edit&type=desktop



this.$router.push({ name: 'projectWorkspace', params: { id: 58, filename: 'sample.docx' }});

