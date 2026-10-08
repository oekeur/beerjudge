import fs from 'node:fs'
import { journey, login, expect, STATE } from './lib.mjs'
const { competitionId } = JSON.parse(fs.readFileSync(STATE))

async function submit(page, styleQuery, abv, notes) {
  await page.locator('#style_search').click()
  await page.locator('#style_search').fill(styleQuery)
  await page.getByTestId('style-option').first().click()
  await page.fill('#abv', String(abv))
  await page.fill('#brewer_notes', notes)
  await page.getByRole('button', { name: /Submit entry|Inzending indienen/ }).click()
  await page.waitForLoadState('networkidle')
}

await journey('M3-submit-entry', async actor => {
  const brewers = [
    ['member3', 'IPA', 6.4], ['member4', 'Stout', 5.1], ['member5', 'IPA', 7.2], ['member6', 'IPA', 4.8],
  ]
  for (const [who, style, abv] of brewers.filter(b => !process.env.ONLY || process.env.ONLY.split(',').includes(b[0]))) {
    const mobile = who === 'member6'
    const { page, step } = await actor(who + (mobile ? '-mobile' : ''), mobile ? { viewport: { width: 390, height: 844 } } : {})
    await login(page, `${who}@beerjudge.local`, who === 'member6' ? (process.env.M6PW ?? 'password') : 'password')
    await step('home card links to entry form', async () => {
      const link = page.locator(`a[href$="/competitions/${competitionId}/entries/create"]`)
      await expect(link.first()).toBeVisible()
      await link.first().click()
      await expect(page).toHaveURL(new RegExp(`/competitions/${competitionId}/entries/create`))
    })
    await step(`submit ${style} entry`, async () => {
      await submit(page, style, abv, `${who} brew notes`)
      await expect(page.getByTestId('flash-success')).toBeVisible()
    })
    if (who === 'member5') {
      await step('second entry in another style', async () => {
        await submit(page, 'Stout', 5.6, 'second beer')
        await expect(page.getByTestId('flash-success')).toBeVisible()
      })
      await step('duplicate style rejected', async () => {
        await submit(page, 'Stout', 5.0, 'dupe')
        await expect(page.getByTestId('form-error')).toBeVisible()
      })
      await step('entry page refused for a competition in judging', async () => {
        const r = await page.goto('/competitions/2/entries/create')
        if (r.status() !== 403) throw new Error('status ' + r.status())
      })
    }
  }
})
