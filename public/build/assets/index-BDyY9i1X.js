import{c as d}from"./createLucideIcon-Bm8ng3_A.js";import{r as n}from"./app-IWj9Cm7L.js";import{u}from"./index-BXMjYIRV.js";/**
 * @license lucide-react v1.22.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const y=[["path",{d:"M3 3v16a2 2 0 0 0 2 2h16",key:"c24i48"}],["path",{d:"M18 17V9",key:"2bz60n"}],["path",{d:"M13 17V5",key:"1frdt8"}],["path",{d:"M8 17v-3",key:"17ska0"}]],v=d("chart-column",y);/**
 * @license lucide-react v1.22.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const b=[["path",{d:"M3 11h3.75a2 2 0 0 1 1.6.8l.45.6a4 4 0 0 0 6.4 0l.45-.6a2 2 0 0 1 1.6-.8H21",key:"1vwh6y"}],["path",{d:"M3 7h18",key:"1uiuf2"}],["rect",{x:"3",y:"3",width:"18",height:"18",rx:"2",key:"h1oib"}]],S=d("wallet-cards",b);function l(r){const[f,t]=n.useState(void 0);return u(()=>{if(r){t({width:r.offsetWidth,height:r.offsetHeight});const a=new ResizeObserver(e=>{if(!Array.isArray(e)||!e.length)return;const h=e[0];let o,i;if("borderBoxSize"in h){const s=h.borderBoxSize,c=Array.isArray(s)?s[0]:s;o=c.inlineSize,i=c.blockSize}else o=r.offsetWidth,i=r.offsetHeight;t({width:o,height:i})});return a.observe(r,{box:"border-box"}),()=>a.unobserve(r)}else t(void 0)},[r]),f}export{v as C,S as W,l as u};
