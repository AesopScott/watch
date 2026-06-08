const fs = require('fs');

const files = ['index.php', 'pricing.php', 'schedule.php', 'why-choose-us.php', 'faq.php'];
const failures = [];

for (const file of files) {
  const content = fs.readFileSync(file, 'utf8');
  const linkPattern = /<a\b[^>]*href=(["'])(.*?)\1[^>]*>([\s\S]*?)<\/a>/gi;
  for (const match of content.matchAll(linkPattern)) {
    const href = match[2];
    const text = match[3].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    const isPassCta = /Start (?:Free Trial|7-Day Pass|7-Day Free Trial)/i.test(text);
    if (isPassCta && href.includes('buy.polar.sh')) {
      failures.push(`${file}: "${text}" still points to Polar checkout`);
    }
  }
}

const pricing = fs.readFileSync('pricing.php', 'utf8');
if (!pricing.includes("'checkout' => '/free-pass.php'")) {
  failures.push('pricing.php: Pro trial checkout must point to /free-pass.php');
}

if (failures.length) {
  console.error('Free-pass CTAs must not require a credit card:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('Free-pass CTAs point to the no-card pass flow');
