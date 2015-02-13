{
   "mailto": "mb@kelnik.ru",
   "hook_secret": "sometherydiffsecret234yyy98",
   "zip_path":
   {
      "url"       : "https://github.com/bubnovKelnik/synczip/archive/master.zip",
      "tmpdir"    : "/tmp/",
      "tmpfile"   : "synczip.tmp.zip"
   },
   "mkdir_chmod": "0777",
   "files_map" :
   {
      "synczip-master/README.md"    : {"saveto": "test/README.md", "chmod": "0777"},
      "synczip-master/composer.json" : {"saveto": "test/composer.json", "chmod": "0777"}
   }
}