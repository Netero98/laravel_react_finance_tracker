# This is where are stored all test scenarios that are not implemented in auto tests. Grouped by endpoints

- GET /dashboard
  - With and without data all charts look good and logical
  - With and without data both light and dark themes look good
  - Курс доллара считается правильно согласно актуальным курсам валют на рынке

- GET /wallets
  - При нажатии на кнопки пагинации в мобиле кнопки нажимаются нормально с 1 раза
  - При добавлении кошелька с несуществующей валютой, высвечивается предупреждение (надо исправить чтобы человек выбирал из списка поддерживаемых валют с удобным поиском)
