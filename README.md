# saiku-backup

This is an **experimental** utility for backing up and restoring [Saiku Analytics] repository and users. It's primary
purpose is restoring state between [kynx/saiku-client] integration tests. It might come in handy for developers 
dumping and reloading stuff, but it is **not** an appropriate tool for backing up a production install.

## Installation

```
composer require kynx/saiku-client kynx/saiku-backup
```

You **must** require `kynx/saiku-client` as well as this package: I can't include the requirement here or we end up
in a circular dependency abyss.


[Saiku Analytics]: https://www.meteorite.bi/products/saiku
[kynx/saiku-client]: https://github.com/kynx/saiku-client
 
