import { CustomWorld } from '../world'
import { When, Then } from '@cucumber/cucumber'
import { expect } from 'chai'

When('I fill {string} field with {string}', async function (this: CustomWorld, name: string, value: string) {
  if (!this.page) {
    throw new Error('Page is undefined')
  }
  const selector = '[name=' + name + ']'
  await this.page.waitForSelector(selector)

  // Очищаем поле ввода
  await this.page.click(selector, { clickCount: 3 }) // Выделяем всё содержимое
  await this.page.keyboard.press('Backspace') // Удаляем выделенное содержимое

  // Вводим новое значение
  await this.page.type(selector, value)
})

When('I check {string} checkbox', async function (this: CustomWorld, name: string) {
  if (!this.page) {
    throw new Error('Page is undefined')
  }
  await this.page.waitForSelector('[name=' + name + ']')
  await this.page.click('[name=' + name + ']')
})

Then('I click submit button', async function (this: CustomWorld) {
  if (!this.page) {
    throw new Error('Page is undefined')
  }
  await this.page.click('button[type=submit]')
})

Then('I see validation error {string}', async function (this: CustomWorld, message: string) {
  if (!this.page) {
    throw new Error('Page is undefined')
  }
  await this.page.waitForSelector('[data-testid=violation]')
  const errors = await this.page.$$eval('[data-testid=violation]', els => els.map(el => el.textContent))
  expect(errors.toString()).to.include(message)
})

Then('I select element with data-value = {string}', async function (this: CustomWorld, id: string) {
  if (!this.page) {
    throw new Error('Page is undefined')
  }

  await this.page.click('[data-value="' + id + '"]')
})
