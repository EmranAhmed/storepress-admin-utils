(()=>{"use strict";var e={d:(t,n)=>{for(var o in n)e.o(n,o)&&!e.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:n[o]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};e.r(t),e.d(t,{createPluginInstance:()=>u,getElement:()=>o,getElements:()=>r,getOptionsFromAttribute:()=>c,getPluginInstance:()=>a,swipeEvent:()=>p,toCamelCase:()=>s,toUpperCamelCase:()=>i,triggerEvent:()=>l});const n=new WeakMap;function o(e){return e?"string"==typeof e?document.querySelector(e):(window.HTMLElement,e):null}function r(e){return e?"string"==typeof e?document.querySelectorAll(e):e instanceof window.HTMLElement?[e]:e:[]}function s(e){return e.replace(/^([A-Z])|[\s-_](\w)/g,((e,t,n)=>n?n.toUpperCase():t.toLowerCase()))}function i(e){return e.replace(/^([a-z])|[\s-_](\w)/g,((e,t,n)=>n?n.toUpperCase():t.toUpperCase()))}function c(e,t){const n=s(t),o=`${n}-`,r={...e.dataset},i=r[n]?r[n].replace(/\'/g,'"'):"{}",c=["true","TRUE","false","FALSE","yes","YES","no","NO","y","Y","n","N"],u=["true","TRUE","yes","YES","y","Y"];try{const t=JSON.parse(i),n=Object.keys(r).filter((e=>e.startsWith(o))).reduce(((t,n)=>{const r=s(n.replace(o,"")),i=e.dataset[n],a=c.includes(i),l="{"===i.charAt(0),p=!1===isNaN(Number(i));return t[r]=i,l&&(t[r]=JSON.parse(i)),p&&(t[r]=Number(i)),a&&(t[r]=u.includes(i)),t}),{});return{...t,...n}}catch(e){return window.console.warn("Seems your settings attribute is not valid JSON. Please wrap keys with quotes.\n\n",e),{}}}function u(e,t,o){const s=r(e),i=[];for(const e of s){let r=n.get(e);n.has(e)||(r=new o(e,t),r.element=e,r.destroy=()=>{n.delete(e),l(e,"destroy")},n.set(e,r)),i.push(r)}return i}function a(e){const t=r(e),o=[];if(0===t.length)return o;for(const e of t)if(n.has(e)){const t=n.get(e);o.push(t)}return o}function l(e,t,n={}){return e.dispatchEvent(new CustomEvent(t,{detail:{...n}}))}function p(e,t,n={}){let o=!1,r=!1,s=0,i=0,c=!1;const u={offset:10,touchOnly:!1,...n},a=e=>{if(o=!0,r=!1,s=e.x,i=e.y,c="touchstart"===e.type,"pointerdown"===e.type&&c)return!1;if(c){const{clientX:t,clientY:n}=e.changedTouches[0];s=t,i=n}},p=t=>{if(!o)return;if("pointermove"===t.type&&c)return!1;let n=t.x-s,a=t.y-i;if(c){const e=t.changedTouches[0];n=e.clientX-s,a=e.clientY-i}r=!0;const p={x:n,y:a,top:a+u.offset<0,bottom:a-u.offset>0,left:n+u.offset<0,right:n-u.offset>0,moving:!0,done:!1};l(e,"swipe",p)},d=t=>{if(!o)return;if(("pointerleave"===t.type||"pointerup"===t.type)&&c)return!1;let n=t.x-s,a=t.y-i;if(c){const{clientX:e,clientY:o}=t.changedTouches[0];n=e-s,a=o-i}if(r){const t={x:n,y:a,top:a+u.offset<0,bottom:a-u.offset>0,left:n+u.offset<0,right:n-u.offset>0,moving:!1,done:!0};l(e,"swipe",t)}r=!1,c=!1,o=!1};return e.addEventListener("touchstart",a,{passive:!0}),e.addEventListener("touchmove",p,{passive:!0}),e.addEventListener("touchend",d,{passive:!0}),e.addEventListener("touchcancel",d),u.touchOnly||(e.addEventListener("pointerdown",a),e.addEventListener("pointermove",p),e.addEventListener("pointerup",d),e.addEventListener("pointerleave",d)),e.addEventListener("swipe",t),()=>{e.removeEventListener("touchstart",a),e.removeEventListener("touchmove",p),e.removeEventListener("touchend",d),e.removeEventListener("touchcancel",d),u.touchOnly||(e.removeEventListener("pointerdown",a),e.removeEventListener("pointermove",p),e.removeEventListener("pointerup",d),e.removeEventListener("pointerleave",d)),e.removeEventListener("swipe",t)}}(window.StorePress=window.StorePress||{}).Utils=t})();