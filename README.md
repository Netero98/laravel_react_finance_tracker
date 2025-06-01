# Finance tracker via Laravel\React\Inertia.js stack

### How are the production and local environments set up?
README.md: https://github.com/dockersamples/laravel-docker-examples

### How to set up the project locally?
```
make init
```

### Tech debt:
- Тесты нормально выполняются на тестовых временных бд только глобальной командой make test. Если запустить конкретный, то будет использована основная бд. Надо сделать так, чтобы тоже использовалась тестовая бд.
- Нужно убедиться, что кеш по курсам валют один на всех юзеров
- Нужно разобраться с PWA, сейчас сделано бездумно как нагенерила нейросеть
