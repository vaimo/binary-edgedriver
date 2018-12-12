# binary-edgedriver

Downloads Microsoft Edge Driver binary on Windows.

The binary version is determined by the following factors:

* what browser version is currently installed (if binary found from the system).
* specified/configured version (see below under 'Configuration' topic).
    
## Configuration

Although the binary downloader usually ends up positively detecting the appropriate 
driver version that needs to be downloaded, user still has an option to specify the 
version explicitly when needed.

```json
{
  "extra": {
    "edgedriver": {
      "version": "6.17134"
    }
  }
}
```
