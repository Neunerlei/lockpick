# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

### [0.0.8](https://github.com/Neunerlei/lockpick/compare/v0.0.7...v0.0.8) (2023-01-04)


### Bug Fixes

* **Override:** allow resolving overrides after building classes ([f1929e0](https://github.com/Neunerlei/lockpick/commit/f1929e0a572b472753791bcc0feea9ae26354aa2))

### [0.0.7](https://github.com/Neunerlei/lockpick/compare/v0.0.6...v0.0.7) (2023-01-04)


### Features

* **Override:** allow overrides to be build without including their files ([eceea28](https://github.com/Neunerlei/lockpick/commit/eceea28455680321681b5ce18889c9e714652a1c))

### [0.0.6](https://github.com/Neunerlei/lockpick/compare/v0.0.5...v0.0.6) (2023-01-04)


### Features

* **Override:** allow forced build of all registered overrides ([bcac446](https://github.com/Neunerlei/lockpick/commit/bcac446964357ecbfdaaab6c4b40d06253dae37a))


### Bug Fixes

* **Override:** remove dev fragment ([7408aae](https://github.com/Neunerlei/lockpick/commit/7408aae0fe57658176c1a66761afb39ca5255a88))

### [0.0.5](https://github.com/Neunerlei/lockpick/compare/v0.0.4...v0.0.5) (2023-01-04)


### Bug Fixes

* **Override:** ensure copy class with full namespace in ClassOverrideContentFilterEvent ([6880364](https://github.com/Neunerlei/lockpick/commit/688036485e4d9f19d2e2a336da995fb602a5198b))

### [0.0.4](https://github.com/Neunerlei/lockpick/compare/v0.0.3...v0.0.4) (2023-01-03)

### [0.0.3](https://github.com/Neunerlei/lockpick/compare/v0.0.2...v0.0.3) (2023-01-03)

### [0.0.2](https://github.com/Neunerlei/lockpick/compare/v0.0.1...v0.0.2) (2023-01-03)


### Features

* clean up testcases and remove "testMode" remnant ([f2b1b84](https://github.com/Neunerlei/lockpick/commit/f2b1b848068bcfd283c24fed9ca80d50612f628d))
* no longer ignore composer.lock and remove .ddev dir from archive ([04a3f8b](https://github.com/Neunerlei/lockpick/commit/04a3f8bbf838be6771b585974a307b4d8ea34d56))
* **Override:** allow userland to re-register already loaded classes ([0b43b67](https://github.com/Neunerlei/lockpick/commit/0b43b671fb29310330c736014b3f14d7abfa0586))
* **Override:** make it easier to detect if ClassOverrider is initialized ([f629ff3](https://github.com/Neunerlei/lockpick/commit/f629ff3d15fcf9acac4367d82f11c23008c5a1e2))


### Bug Fixes

* **ClassLockpick:** ensure that __isset check if the value is actually set ([68acaef](https://github.com/Neunerlei/lockpick/commit/68acaefa8a27f73f2a1e36fff87c6b4e756c1776))
* fix code generator factory ([703e918](https://github.com/Neunerlei/lockpick/commit/703e918b12bd67d83da38af1ecc55199ca35d0e8))

### 0.0.1 (2023-01-02)


### Features

* initial commit ([eb36013](https://github.com/Neunerlei/lockpick/commit/eb36013a559ec8b93535f3610d791bb7576993f6))


### Bug Fixes

* add compatibility with php preloading ([1d418be](https://github.com/Neunerlei/lockpick/commit/1d418be8094a10fb8619ad1663dfa249319d82c7))
