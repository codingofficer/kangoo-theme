import fs from 'node:fs/promises';
import path from 'node:path';
import { FileBlob, SpreadsheetFile, Workbook } from '@oai/artifact-tool';

const root = 'C:/Users/sheri/Documents/GitHub/kangoo-theme';
const outputDir = path.join(root, 'outputs', 'seo-growth-20260614');
const domainCsv = 'E:/semrush/gap.keywords_2026-06-14T21_03_56.377Z.csv';
const categoryCsv = 'E:/semrush/gap.keywords_2026-06-14T21_03_02.536Z.csv';
const topicXlsx = 'E:/semrush/topic-research-20260614.xlsx';
const outputPath = path.join(outputDir, 'Kangoo_SEO_90_Day_Keyword_Map.xlsx');

function parseCsv(text) {
  const rows = [];
  let row = [];
  let cell = '';
  let quoted = false;
  for (let i = 0; i < text.length; i += 1) {
    const char = text[i];
    if (quoted) {
      if (char === '"' && text[i + 1] === '"') {
        cell += '"';
        i += 1;
      } else if (char === '"') {
        quoted = false;
      } else {
        cell += char;
      }
    } else if (char === '"') {
      quoted = true;
    } else if (char === ',') {
      row.push(cell);
      cell = '';
    } else if (char === '\n') {
      row.push(cell.replace(/\r$/, ''));
      rows.push(row);
      row = [];
      cell = '';
    } else {
      cell += char;
    }
  }
  if (cell !== '' || row.length) {
    row.push(cell);
    rows.push(row);
  }
  return rows;
}

const base = 'https://kangoopouches.co.uk';
const routes = {
  core: `${base}/product-category/nicotine-pouches/`,
  p99: `${base}/product-category/99p-pouches/`,
  what: `${base}/blog/what-are-nicotine-pouches/`,
  how: `${base}/blog/how-to-use-nicotine-pouches-placement-timing-and-tips/`,
  side: `${base}/blog/nicotine-pouches-side-effects-what-adult-users-should-know/`,
  best: `${base}/blog/best-nicotine-pouches-uk/`,
  velo: `${base}/product-category/velo/`,
  zyn: `${base}/product-category/zyn/`,
  nordic: `${base}/product-category/nordic-spirit/`,
  killa: `${base}/product-category/killa/`,
  pablo: `${base}/product-category/pablo/`,
  fumi: `${base}/product-category/fumi/`,
  xqs: `${base}/product-category/xqs/`,
  ubbs: `${base}/product-category/ubbs/`,
  veloDots: `${base}/blog/velo-strength-dots-explained/`,
  whatZyn: `${base}/blog/what-is-zyn-uk-guide-to-zyn-nicotine-pouches/`,
  veloNordic: `${base}/blog/velo-vs-nordic-spirit/`,
  zynNordic: `${base}/blog/zyn-vs-nordic-spirit/`,
  zynVelo: `${base}/blog/zyn-vs-velo/`,
  nordicReview: `${base}/blog/nordic-spirit-reviews/`,
  veloGuide: `${base}/blog/velo-nicotine-pouches-guide-flavours-strengths-and-best-picks/`,
  zynGuide: `${base}/blog/zyn-nicotine-pouches-guide-strengths-flavours-and-best-picks/`,
  snus: `${base}/blog/what-is-snus/`,
  snusUk: `${base}/blog/snus-uk/`,
  snusVs: `${base}/blog/snus-vs-nicotine-pouches-what-is-the-difference-in-the-uk/`,
  mg3: `${base}/blog/3mg-nicotine-pouches/`,
  low: `${base}/blog/low-strength-nicotine-pouches/`,
  strong: `${base}/blog/strongest-nicotine-pouches-uk-strong-and-extra-strong-options-explained/`,
  mint: `${base}/mint-nicotine-pouches/`,
  berry: `${base}/berry-nicotine-pouches/`,
  freezing: `${base}/product/velo/freezing-peppermint-10-9mg/`,
};

const unavailable = /\b(lost mary|elf bar|cuba|siberia|iceberg|ace superwhite|rogue|on nicotine|zone nicotine|snus vikings|northerner|haypp|two wombats|zyn discount code|velo discount code|nordic spirit discount code)\b/i;
const irrelevant = /\b(vape|vaping|e[- ]?cig|cigarette|popcorn lung|nicotine patch|nicotine gum|lozenge|free sample|amazon|tesco|coupon code|discount code|quit smoking|smoking cessation)\b/i;

function classify(keyword, intent, volume, kd) {
  const k = keyword.toLowerCase().trim();
  const commercial = /commercial|transactional/i.test(intent);
  let result = { status: 'Deferred', cluster: 'Research queue', target: '', type: 'Research', action: 'Review when supported by stock or Search Console', rationale: 'Related query, but no distinct supported page is approved yet.', priority: 'P3', wave: 'Evidence-led backlog' };

  if (!k || irrelevant.test(k)) return { ...result, status: 'Rejected', cluster: 'Out of scope', action: 'Do not target', rationale: 'Irrelevant, unsupported service, competitor code, free sample or cessation intent.', priority: 'None', wave: 'Rejected' };
  if (unavailable.test(k)) return { ...result, status: 'Rejected', cluster: 'Unavailable brand', action: 'Do not create landing page', rationale: 'Kangoo does not currently stock or support this brand/query.', priority: 'None', wave: 'Rejected' };

  if (/\b99p\b|cheap nicotine pouch/.test(k)) return { ...result, status: 'Approved', cluster: '99p/value', target: routes.p99, type: 'Category', action: 'Protect and improve', rationale: 'Established ranking entity; 79p remains a temporary promotion.', priority: 'P1', wave: 'Days 1-14' };
  if (k === 'nicotine pouches' || k === 'nicotine pouch') return { ...result, status: 'Approved', cluster: 'Core commercial', target: routes.core, type: 'Category', action: 'Build main commercial authority', rationale: 'The main product category owns the principal generic query.', priority: 'P1', wave: 'Days 8-25' };
  if (/what are nicotine pouches|what is a nicotine pouch|how do nicotine pouches work|nicotine pouch meaning/.test(k)) return { ...result, status: 'Approved', cluster: 'Basics', target: routes.what, type: 'Guide', action: 'Answer-first source-led guide', rationale: 'Informational intent distinct from shopping.', priority: 'P1', wave: 'Days 8-25' };
  if (/how to use nicotine pouches|how do you use nicotine pouches|where to put nicotine pouch/.test(k)) return { ...result, status: 'Approved', cluster: 'How-to', target: routes.how, type: 'Guide', action: 'Improve practical guidance', rationale: 'Placement and usage intent.', priority: 'P1', wave: 'Days 8-25' };
  if (/side effect|are nicotine pouches safe|nicotine pouch safety|bad for you/.test(k)) return { ...result, status: 'Approved', cluster: 'Safety', target: routes.side, type: 'Guide', action: 'Source-led upgrade only', rationale: 'Sensitive health intent requires authoritative sources and careful claims.', priority: 'P2', wave: 'Days 40-75' };
  if (/best nicotine pouches/.test(k)) return { ...result, status: 'Approved', cluster: 'Best/selection', target: routes.best, type: 'Guide', action: 'Upgrade with transparent criteria', rationale: 'Existing guide owns selection intent.', priority: 'P2', wave: 'Days 40-75' };
  if (/velo.*nordic spirit|nordic spirit.*velo/.test(k)) return { ...result, status: 'Approved', cluster: 'Comparisons', target: routes.veloNordic, type: 'Comparison', action: 'Expand factual comparison', rationale: 'Distinct brand comparison intent.', priority: 'P2', wave: 'Days 40-75' };
  if (/zyn.*nordic spirit|nordic spirit.*zyn/.test(k)) return { ...result, status: 'Approved', cluster: 'Comparisons', target: routes.zynNordic, type: 'Comparison', action: 'Expand factual comparison', rationale: 'Distinct brand comparison intent.', priority: 'P2', wave: 'Days 40-75' };
  if (/zyn.*velo|velo.*zyn/.test(k)) return { ...result, status: 'Approved', cluster: 'Comparisons', target: routes.zynVelo, type: 'Comparison', action: 'Consolidate comparison authority', rationale: 'One canonical comparison page.', priority: 'P2', wave: 'Days 40-75' };
  if (/velo.*(dot|dots)/.test(k)) return { ...result, status: 'Approved', cluster: 'VELO dots', target: routes.veloDots, type: 'Guide', action: 'Create consolidated 1-6 dot guide', rationale: 'One page covers the closely related dot query family.', priority: 'P1', wave: 'Days 20-50' };
  if (/freezing peppermint/.test(k)) return { ...result, status: 'Approved', cluster: 'Product', target: routes.freezing, type: 'Product', action: 'Strengthen exact product page', rationale: 'Exact available product intent.', priority: 'P1', wave: 'Days 20-50' };
  if (/ruby berry|crispy peppermint|black cherry/.test(k) && /velo|zyn|nicotine/.test(k)) return { ...result, status: 'Approved', cluster: 'Product', target: k.includes('ruby berry') ? `${base}/product/velo/ruby-berry-10mg/` : k.includes('crispy') ? `${base}/product/velo/crispy-peppermint-6mg/` : `${base}/product/zyn/zyn-black-cherry-mini-3mg/`, type: 'Product', action: 'Strengthen exact product page', rationale: 'Product query belongs on the existing product.', priority: 'P1', wave: 'Days 20-50' };
  if (/what is zyn|zyn meaning/.test(k)) return { ...result, status: 'Approved', cluster: 'ZYN education', target: routes.whatZyn, type: 'Guide', action: 'Keep educational intent distinct', rationale: 'Definition intent differs from shopping.', priority: 'P2', wave: 'Days 20-50' };
  if (/velo review/.test(k)) return { ...result, status: 'Approved', cluster: 'VELO', target: routes.veloGuide, type: 'Guide', action: 'Consolidate into VELO guide', rationale: 'Avoid thin review cannibalisation.', priority: 'P1', wave: 'Days 1-14' };
  if (/nordic spirit review/.test(k)) return { ...result, status: 'Approved', cluster: 'Nordic Spirit', target: routes.nordicReview, type: 'Review guide', action: 'Expand with genuine evidence', rationale: 'Existing distinct review intent.', priority: 'P2', wave: 'Days 20-50' };
  if (/\bvelo\b/.test(k)) return { ...result, status: 'Approved', cluster: 'VELO', target: routes.velo, type: 'Category', action: 'Deepen commercial category', rationale: commercial ? 'Transactional VELO intent.' : 'Brand intent is best served by the live VELO hub.', priority: 'P1', wave: 'Days 20-50' };
  if (/\bzyn\b/.test(k)) return { ...result, status: 'Approved', cluster: 'ZYN', target: routes.zyn, type: 'Category', action: 'Deepen commercial category', rationale: commercial ? 'Transactional ZYN intent.' : 'Brand intent is best served by the live ZYN hub.', priority: 'P1', wave: 'Days 20-50' };
  if (/nordic spirit/.test(k)) return { ...result, status: 'Approved', cluster: 'Nordic Spirit', target: routes.nordic, type: 'Category', action: 'Retain strategic brand page', rationale: 'Recognised brand demand despite a small current range.', priority: 'P1', wave: 'Days 20-50' };
  if (/\bkilla\b/.test(k)) return { ...result, status: 'Approved', cluster: 'KILLA', target: routes.killa, type: 'Category', action: 'Improve stocked brand page', rationale: 'Supported brand; qualify snus wording accurately.', priority: 'P2', wave: 'Days 20-50' };
  if (/\bpablo\b/.test(k)) return { ...result, status: 'Approved', cluster: 'PABLO', target: routes.pablo, type: 'Category', action: 'Improve stocked brand page', rationale: 'Supported brand; qualify snus wording accurately.', priority: 'P2', wave: 'Days 20-50' };
  if (/\bfumi\b/.test(k)) return { ...result, status: 'Approved', cluster: 'FUMi', target: routes.fumi, type: 'Category', action: 'Improve stocked brand page', rationale: 'Supported stocked brand.', priority: 'P3', wave: 'Days 40-75' };
  if (/\bxqs\b/.test(k)) return { ...result, status: 'Approved', cluster: 'XQS', target: routes.xqs, type: 'Category', action: 'Improve stocked brand page', rationale: 'Supported stocked brand.', priority: 'P3', wave: 'Days 40-75' };
  if (/\bubbs\b/.test(k)) return { ...result, status: 'Approved', cluster: 'Ubbs', target: routes.ubbs, type: 'Category', action: 'Retain only while useful stock exists', rationale: 'Supported brand with a very small range.', priority: 'P3', wave: 'Evidence-led backlog' };
  if (/3mg nicotine pouch/.test(k)) return { ...result, status: 'Approved', cluster: 'Strength', target: routes.mg3, type: 'Guide', action: 'Upgrade lower-strength guide', rationale: 'Exact strength demand with useful products.', priority: 'P2', wave: 'Days 40-75' };
  if (/low strength nicotine pouch|mild nicotine pouch|light nicotine pouch/.test(k)) return { ...result, status: 'Approved', cluster: 'Strength', target: routes.low, type: 'Guide', action: 'Upgrade lower-strength guide', rationale: 'Distinct strength-selection intent.', priority: 'P2', wave: 'Days 40-75' };
  if (/strong nicotine pouch|strongest nicotine pouch|extra strong nicotine pouch/.test(k)) return { ...result, status: 'Approved', cluster: 'Strength', target: routes.strong, type: 'Guide', action: 'Consolidate strong-pouch guidance', rationale: 'One source-led strength guide avoids overlap.', priority: 'P2', wave: 'Days 40-75' };
  if (/mint nicotine pouch|peppermint nicotine pouch|spearmint nicotine pouch/.test(k)) return { ...result, status: 'Approved', cluster: 'Flavour', target: routes.mint, type: 'Flavour hub', action: 'Upgrade existing mint page', rationale: 'Supported flavour with useful inventory.', priority: 'P2', wave: 'Days 40-75' };
  if (/berry nicotine pouch/.test(k)) return { ...result, status: 'Approved', cluster: 'Flavour', target: routes.berry, type: 'Flavour hub', action: 'Upgrade existing berry page', rationale: 'Supported flavour with useful inventory.', priority: 'P2', wave: 'Days 40-75' };
  if (/snus vs nicotine pouch|nicotine pouch vs snus/.test(k)) return { ...result, status: 'Approved', cluster: 'Snus education', target: routes.snusVs, type: 'Guide', action: 'Keep comparison distinct', rationale: 'Comparison intent; clearly distinguish tobacco snus.', priority: 'P2', wave: 'Days 40-75' };
  if (/snus uk|is snus legal|snus legal/.test(k)) return { ...result, status: 'Approved', cluster: 'Snus education', target: routes.snusUk, type: 'Guide', action: 'Source-led legal guide', rationale: 'UK legality intent.', priority: 'P2', wave: 'Days 40-75' };
  if (/what is snus|snus meaning/.test(k)) return { ...result, status: 'Approved', cluster: 'Snus education', target: routes.snus, type: 'Guide', action: 'Definition guide', rationale: 'Definition intent; explain tobacco-free alternatives accurately.', priority: 'P2', wave: 'Days 40-75' };
  if (/nicotine pouch/.test(k) && (commercial || /\buk\b|buy|online|shop|price/.test(k))) return { ...result, status: 'Approved', cluster: 'Core commercial', target: routes.core, type: 'Category', action: 'Build main commercial authority', rationale: 'Commercial nicotine-pouch query belongs to the main category.', priority: Number(volume) >= 1000 ? 'P1' : 'P2', wave: 'Days 8-25' };
  if (/nicotine pouch|snus/.test(k)) return { ...result, status: 'Deferred', cluster: 'Related research', action: 'Review against Search Console before publishing', rationale: 'Relevant, but current intent or page ownership is not clear enough for approval.', priority: Number(volume) >= 500 ? 'P2' : 'P3', wave: 'Evidence-led backlog' };
  return { ...result, status: 'Rejected', cluster: 'Out of scope', action: 'Do not target', rationale: 'No supported relationship to the live Kangoo catalogue or editorial scope.', priority: 'None', wave: 'Rejected' };
}

function styleHeader(sheet, range) {
  const r = sheet.getRange(range);
  r.format.fill = '#0F172A';
  r.format.font = { bold: true, color: '#FFFFFF', size: 10 };
  r.format.wrapText = true;
  r.format.verticalAlignment = 'center';
}

function styleTitle(sheet, range) {
  const r = sheet.getRange(range);
  r.format.fill = '#FFF4E8';
  r.format.font = { bold: true, color: '#111827', size: 18 };
  r.format.verticalAlignment = 'center';
}

function addTableSheet(workbook, name, matrix, widths) {
  const sheet = workbook.worksheets.add(name);
  sheet.showGridLines = false;
  const rows = matrix.length;
  const cols = matrix[0].length;
  const endCol = columnName(cols);
  sheet.getRange(`A1:${endCol}${rows}`).values = matrix;
  styleHeader(sheet, `A1:${endCol}1`);
  sheet.getRange(`A1:${endCol}${rows}`).format.verticalAlignment = 'top';
  sheet.getRange(`A1:${endCol}${rows}`).format.wrapText = true;
  sheet.freezePanes.freezeRows(1);
  widths.forEach((width, index) => {
    sheet.getRange(`${columnName(index + 1)}:${columnName(index + 1)}`).format.columnWidth = width;
  });
  if (rows > 1) sheet.tables.add(`A1:${endCol}${rows}`, true, `${name.replace(/[^A-Za-z0-9]/g, '')}Table`);
  return sheet;
}

function columnName(number) {
  let result = '';
  let n = number;
  while (n > 0) {
    n -= 1;
    result = String.fromCharCode(65 + (n % 26)) + result;
    n = Math.floor(n / 26);
  }
  return result;
}

const [domainText, categoryText] = await Promise.all([
  fs.readFile(domainCsv, 'utf8'),
  fs.readFile(categoryCsv, 'utf8'),
]);
const domainRows = parseCsv(domainText);
const categoryRows = parseCsv(categoryText);
const domainHeader = domainRows[0];
const domainObjects = domainRows.slice(1).filter(r => r[0]).map(row => Object.fromEntries(domainHeader.map((header, i) => [header, row[i] ?? ''])));
const categoryHeader = categoryRows[0];

const topicBlob = await FileBlob.load(topicXlsx);
const topicWorkbook = await SpreadsheetFile.importXlsx(topicBlob);
const topicValues = topicWorkbook.worksheets.items[0].getUsedRange().values;

const keywordRows = domainObjects.map(row => {
  const volume = Number(row.Volume || 0);
  const kd = Number(row['Keyword Difficulty'] || 0);
  const c = classify(row.Keyword, row.Intents, volume, kd);
  const competitorRanks = [Number(row['haypp.com'] || 0), Number(row['northerner.com'] || 0), Number(row['snusvikings.co.uk'] || 0)].filter(n => n > 0);
  return [row.Keyword, row.Intents, volume, kd, Number(row.CPC || 0), Number(row['kangoopouches.co.uk'] || 0) || '', competitorRanks.length ? Math.min(...competitorRanks) : '', c.status, c.cluster, c.target, c.type, c.action, c.rationale, c.priority, c.wave];
});

const approved = keywordRows.filter(row => row[7] === 'Approved');
const deferred = keywordRows.filter(row => row[7] === 'Deferred');
const rejected = keywordRows.filter(row => row[7] === 'Rejected');
const clusters = new Map();
for (const row of approved) {
  const cluster = row[8];
  const current = clusters.get(cluster) || { keywords: 0, volume: 0 };
  current.keywords += 1;
  current.volume += Number(row[2] || 0);
  clusters.set(cluster, current);
}
const clusterRows = [...clusters.entries()].sort((a, b) => b[1].volume - a[1].volume);

const workbook = Workbook.create();
const dashboard = workbook.worksheets.add('Executive Summary');
dashboard.showGridLines = false;
dashboard.mergeCells('A1:H2');
dashboard.getRange('A1').values = [['Kangoo 90-Day SEO Growth Map']];
styleTitle(dashboard, 'A1:H2');
dashboard.getRange('A4:B8').values = [
  ['Metric', 'Value'],
  ['SEMrush domain-gap terms', domainObjects.length],
  ['Approved keyword assignments', approved.length],
  ['Deferred for evidence', deferred.length],
  ['Rejected/no-target terms', rejected.length],
];
styleHeader(dashboard, 'A4:B4');
dashboard.getRange('D4:H8').values = [
  ['Operating rule', 'Decision', '', '', ''],
  ['Ranking truth', 'Google Search Console', '', '', ''],
  ['Opportunity evidence', 'SEMrush exports and competitor structure', '', '', ''],
  ['Main commercial owner', '/product-category/nicotine-pouches/', '', '', ''],
  ['99p policy', 'Keep the 99p entity; 79p is promotional', '', '', ''],
];
styleHeader(dashboard, 'D4:H4');
dashboard.getRange('A11:C11').values = [['Approved cluster', 'Keywords', 'Search volume']];
styleHeader(dashboard, 'A11:C11');
dashboard.getRange(`A12:C${11 + clusterRows.length}`).values = clusterRows.map(([name, values]) => [name, values.keywords, values.volume]);
dashboard.getRange(`C12:C${11 + clusterRows.length}`).format.numberFormat = '#,##0';
dashboard.getRange('A:A').format.columnWidth = 30;
dashboard.getRange('B:C').format.columnWidth = 16;
dashboard.getRange('D:D').format.columnWidth = 24;
dashboard.getRange('E:H').format.columnWidth = 18;
const chart = dashboard.charts.add('bar', dashboard.getRange(`A11:C${11 + Math.min(clusterRows.length, 12)}`));
chart.title = 'Approved Opportunity by Cluster';
chart.hasLegend = true;
chart.setPosition('E11', 'L28');

const headers = ['Keyword', 'Intent', 'Volume', 'KD', 'CPC', 'Kangoo Rank', 'Best Competitor Rank', 'Status', 'Cluster', 'Canonical Target', 'Page Type', 'Action', 'Rationale', 'Priority', 'Wave'];
const approvedSheet = addTableSheet(workbook, 'Approved Keyword Map', [headers, ...approved], [30, 18, 11, 9, 10, 12, 16, 11, 18, 52, 16, 28, 52, 10, 18]);
approvedSheet.getRange(`C2:C${approved.length + 1}`).format.numberFormat = '#,##0';
approvedSheet.getRange(`E2:E${approved.length + 1}`).format.numberFormat = '£0.00';
const deferredSheet = addTableSheet(workbook, 'Deferred Keywords', [headers, ...deferred], [30, 18, 11, 9, 10, 12, 16, 11, 18, 52, 16, 28, 52, 10, 18]);
const rejectedSheet = addTableSheet(workbook, 'Rejected Keywords', [headers, ...rejected], [30, 18, 11, 9, 10, 12, 16, 11, 18, 52, 16, 28, 52, 10, 18]);

const roadmap = [
  ['Wave', 'URL/Page', 'Primary Cluster', 'Action', 'Success Signal'],
  ['Days 1-14', routes.core, 'Core commercial', 'Repair canonicals, modules, copy and indexing rules', 'Clean crawl and stable query ownership'],
  ['Days 1-14', routes.p99, '99p/value', 'Protect 99p entity; keep 79p temporary', 'Retain/improve top-10 ranking'],
  ['Days 1-14', routes.zynGuide, 'ZYN', 'Merge duplicate ZYN guide', 'One canonical guide'],
  ['Days 1-14', routes.veloGuide, 'VELO', 'Merge thin VELO review', 'One stronger guide'],
  ['Days 8-25', routes.what, 'Basics', 'Answer-first source-led rewrite', 'Featured snippet/AI citation impressions'],
  ['Days 8-25', routes.how, 'How-to', 'Improve placement and disposal guidance', 'More non-brand impressions'],
  ['Days 20-50', routes.velo, 'VELO', 'Deepen live brand category', 'Positions 4-20 improve'],
  ['Days 20-50', routes.zyn, 'ZYN', 'Deepen live brand category', 'Positions 4-20 improve'],
  ['Days 20-50', routes.nordic, 'Nordic Spirit', 'Retain despite small range; keep accurate', 'Indexed and relevant'],
  ['Days 20-50', routes.veloDots, 'VELO dots', 'Publish one consolidated dot guide', 'Dot-query impressions consolidate'],
  ['Days 20-50', routes.freezing, 'Product', 'Upgrade exact product page', 'Product query CTR and rank'],
  ['Days 40-75', routes.best, 'Best/selection', 'Transparent criteria and current stock', 'Position and CTR improvement'],
  ['Days 40-75', routes.side, 'Safety', 'Source-led update only', 'Trusted informational visibility'],
  ['Days 40-75', routes.mg3, 'Strength', 'Upgrade 3mg guide', 'Long-tail impressions'],
  ['Days 40-75', routes.low, 'Strength', 'Upgrade low-strength guide', 'Long-tail impressions'],
  ['Days 40-75', routes.strong, 'Strength', 'Consolidate strong guides', 'No cannibalisation'],
  ['Days 40-75', routes.mint, 'Flavour', 'Improve existing hub only', 'Mint query growth'],
  ['Days 40-75', routes.berry, 'Flavour', 'Improve existing hub only', 'Berry query growth'],
  ['Days 75-90', 'Search Console query-to-page report', 'All', 'Iterate pages in positions 4-20', 'Clicks, CTR and average position'],
];
addTableSheet(workbook, '90-Day Roadmap', roadmap, [16, 54, 20, 48, 34]);

const redirects = [
  ['Source URL', 'Destination URL', 'Reason', 'Status'],
  [`${base}/brand/velo/`, routes.velo, 'Canonical brand taxonomy consolidation', '301'],
  [`${base}/brand/zyn/`, routes.zyn, 'Canonical brand taxonomy consolidation', '301'],
  [`${base}/blog/zyn-pouches-guide-flavours-strengths-and-buying-tips/`, routes.zynGuide, 'Merge duplicate ZYN guide', '301'],
  [`${base}/blog/velo-reviews/`, routes.veloGuide, 'Merge thin VELO review', '301'],
  [`${base}/blog/what-are-nicotine-pouches-a-complete-uk-guide/`, routes.what, 'Merge duplicate beginner guide', '301'],
  [`${base}/blog/nicotine-pouches-for-beginners/`, routes.what, 'Merge duplicate beginner guide', '301'],
];
addTableSheet(workbook, 'Redirect Map', redirects, [54, 54, 42, 10]);

const pageMap = [
  ['Canonical Page', 'Primary Target', 'Intent', 'Role', 'Do Not Cannibalise With'],
  [routes.core, 'nicotine pouches UK', 'Commercial', 'Main catalogue authority', 'Homepage and beginner guide'],
  [routes.what, 'what are nicotine pouches', 'Informational', 'Answer-first definition guide', 'Commercial category'],
  [routes.p99, '99p nicotine pouches', 'Commercial', 'Permanent value collection', '79p as a permanent entity'],
  [routes.velo, 'VELO nicotine pouches', 'Commercial', 'Live VELO catalogue', 'VELO editorial guide'],
  [routes.zyn, 'ZYN pouches', 'Commercial', 'Live ZYN catalogue', 'What is ZYN guide'],
  [routes.nordic, 'Nordic Spirit nicotine pouches', 'Commercial', 'Live strategic brand page', 'Nordic Spirit review'],
  [routes.veloDots, 'VELO strength dots', 'Informational', 'Consolidated 1-6 dot guide', 'Separate thin dot pages'],
  [routes.snus, 'what is snus', 'Informational', 'Definition', 'Snus UK legality'],
  [routes.snusUk, 'snus UK', 'Informational/legal', 'UK rules', 'Definition and product category'],
  [routes.snusVs, 'snus vs nicotine pouches', 'Comparison', 'Terminology comparison', 'Commercial category'],
];
addTableSheet(workbook, 'Canonical Page Map', pageMap, [54, 30, 18, 38, 42]);

const rawDomain = [domainHeader, ...domainRows.slice(1).map(row => domainHeader.map((_, index) => row[index] ?? ''))];
const rawCategory = [categoryHeader, ...categoryRows.slice(1).map(row => categoryHeader.map((_, index) => row[index] ?? ''))];
addTableSheet(workbook, 'Raw Domain Gap', rawDomain, Array(domainHeader.length).fill(18).map((w, i) => i === 0 ? 30 : w));
addTableSheet(workbook, 'Raw Category Gap', rawCategory, Array(categoryHeader.length).fill(18).map((w, i) => i === 0 ? 30 : w));
addTableSheet(workbook, 'Raw Topic Research', topicValues, [22, 24, 14, 12, 16, 44, 18, 15, 54, 12]);

await fs.mkdir(outputDir, { recursive: true });
for (const sheet of workbook.worksheets.items) {
  const used = sheet.getUsedRange().values;
  const previewRange = `A1:${columnName(used[0].length)}${Math.min(used.length, 25)}`;
  const preview = await workbook.render({ sheetName: sheet.name, range: previewRange, scale: 0.8, format: 'png' });
  await fs.writeFile(path.join(outputDir, `preview-${sheet.name.replace(/[^A-Za-z0-9]+/g, '-').toLowerCase()}.png`), new Uint8Array(await preview.arrayBuffer()));
}
const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(JSON.stringify({ outputPath, sheets: workbook.worksheets.items.map(s => s.name), approved: approved.length, deferred: deferred.length, rejected: rejected.length, clusterRows }, null, 2));
