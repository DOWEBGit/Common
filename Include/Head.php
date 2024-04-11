<style>
    .loaderContainer {
        position: fixed;
        top: 0;
        bottom: 0;
        width: 100%;
        background-color: rgba(0, 0, 0, 0.05);
        z-index: 1000;
    }

    .loader {
        position: fixed;
        z-index: 1000;
        margin: auto;
        border: 5px solid #EAF0F6;
        border-radius: 50%;
        border-top: 5px solid #FF7A59;
        width: 100px;
        height: 100px;
        animation: spinner 4s linear infinite;
        top: calc(50% - 50px);
        left: calc(50% - 50px);
    }

    @keyframes spinner {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<!--suppress CommaExpressionJS, JSUnresolvedReference, JSReferencingMutableVariableFromClosure -->
<script>
    //https://github.com/fiduswriter/diffDOM
    var diffDOM=function(e){"use strict";var t=function(){return t=Object.assign||function(e){for(var t,n=arguments,o=1,s=arguments.length;o<s;o++)for(var i in t=n[o])Object.prototype.hasOwnProperty.call(t,i)&&(e[i]=t[i]);return e},t.apply(this,arguments)};function n(e,t,n){if(n||2===arguments.length)for(var o,s=0,i=t.length;s<i;s++)!o&&s in t||(o||(o=Array.prototype.slice.call(t,0,s)),o[s]=t[s]);return e.concat(o||Array.prototype.slice.call(t))}"function"==typeof SuppressedError&&SuppressedError;var o=function(){function e(e){void 0===e&&(e={});var t=this;Object.entries(e).forEach((function(e){var n=e[0],o=e[1];return t[n]=o}))}return e.prototype.toString=function(){return JSON.stringify(this)},e.prototype.setValue=function(e,t){return this[e]=t,this},e}();function s(e){for(var t=arguments,n=[],o=1;o<arguments.length;o++)n[o-1]=t[o];return null!=e&&n.some((function(t){var n,o;return"function"==typeof(null===(o=null===(n=null==e?void 0:e.ownerDocument)||void 0===n?void 0:n.defaultView)||void 0===o?void 0:o[t])&&e instanceof e.ownerDocument.defaultView[t]}))}function i(e,t,n){var o;return"#text"===e.nodeName?o=n.document.createTextNode(e.data):"#comment"===e.nodeName?o=n.document.createComment(e.data):(t?(o=n.document.createElementNS("http://www.w3.org/2000/svg",e.nodeName),"foreignObject"===e.nodeName&&(t=!1)):"svg"===e.nodeName.toLowerCase()?(o=n.document.createElementNS("http://www.w3.org/2000/svg","svg"),t=!0):o=n.document.createElement(e.nodeName),e.attributes&&Object.entries(e.attributes).forEach((function(e){var t=e[0],n=e[1];return o.setAttribute(t,n)})),e.childNodes&&e.childNodes.forEach((function(e){return o.appendChild(i(e,t,n))})),n.valueDiffing&&(e.value&&s(o,"HTMLButtonElement","HTMLDataElement","HTMLInputElement","HTMLLIElement","HTMLMeterElement","HTMLOptionElement","HTMLProgressElement","HTMLParamElement")&&(o.value=e.value),e.checked&&s(o,"HTMLInputElement")&&(o.checked=e.checked),e.selected&&s(o,"HTMLOptionElement")&&(o.selected=e.selected))),o}var a=function(e,t){for(t=t.slice();t.length>0;){var n=t.splice(0,1)[0];e=e.childNodes[n]}return e};function l(e,t,o){var l,c,r,u=t[o._const.action],d=t[o._const.route];[o._const.addElement,o._const.addTextElement].includes(u)||(l=a(e,d));var h={diff:t,node:l};if(o.preDiffApply(h))return!0;switch(u){case o._const.addAttribute:if(!l||!s(l,"Element"))return!1;l.setAttribute(t[o._const.name],t[o._const.value]);break;case o._const.modifyAttribute:if(!l||!s(l,"Element"))return!1;l.setAttribute(t[o._const.name],t[o._const.newValue]),s(l,"HTMLInputElement")&&"value"===t[o._const.name]&&(l.value=t[o._const.newValue]);break;case o._const.removeAttribute:if(!l||!s(l,"Element"))return!1;l.removeAttribute(t[o._const.name]);break;case o._const.modifyTextElement:if(!l||!s(l,"Text"))return!1;o.textDiff(l,l.data,t[o._const.oldValue],t[o._const.newValue]),s(l.parentNode,"HTMLTextAreaElement")&&(l.parentNode.value=t[o._const.newValue]);break;case o._const.modifyValue:if(!l||void 0===l.value)return!1;l.value=t[o._const.newValue];break;case o._const.modifyComment:if(!l||!s(l,"Comment"))return!1;o.textDiff(l,l.data,t[o._const.oldValue],t[o._const.newValue]);break;case o._const.modifyChecked:if(!l||void 0===l.checked)return!1;l.checked=t[o._const.newValue];break;case o._const.modifySelected:if(!l||void 0===l.selected)return!1;l.selected=t[o._const.newValue];break;case o._const.replaceElement:var f="svg"===t[o._const.newValue].nodeName.toLowerCase()||"http://www.w3.org/2000/svg"===l.parentNode.namespaceURI;l.parentNode.replaceChild(i(t[o._const.newValue],f,o),l);break;case o._const.relocateGroup:n([],new Array(t[o._const.groupLength]),!0).map((function(){return l.removeChild(l.childNodes[t[o._const.from]])})).forEach((function(e,n){0===n&&(r=l.childNodes[t[o._const.to]]),l.insertBefore(e,r||null)}));break;case o._const.removeElement:l.parentNode.removeChild(l);break;case o._const.addElement:var p=(_=d.slice()).splice(_.length-1,1)[0];if(!s(l=a(e,_),"Element"))return!1;l.insertBefore(i(t[o._const.element],"http://www.w3.org/2000/svg"===l.namespaceURI,o),l.childNodes[p]||null);break;case o._const.removeTextElement:if(!l||3!==l.nodeType)return!1;var m=l.parentNode;m.removeChild(l),s(m,"HTMLTextAreaElement")&&(m.value="");break;case o._const.addTextElement:var _;p=(_=d.slice()).splice(_.length-1,1)[0];if(c=o.document.createTextNode(t[o._const.value]),!(l=a(e,_)).childNodes)return!1;l.insertBefore(c,l.childNodes[p]||null),s(l.parentNode,"HTMLTextAreaElement")&&(l.parentNode.value=t[o._const.value]);break;default:console.log("unknown action")}return o.postDiffApply({diff:h.diff,node:h.node,newNode:c}),!0}function c(e,t,n){var o=e[t];e[t]=e[n],e[n]=o}function r(e,t,n){(t=t.slice()).reverse(),t.forEach((function(t){!function(e,t,n){switch(t[n._const.action]){case n._const.addAttribute:t[n._const.action]=n._const.removeAttribute,l(e,t,n);break;case n._const.modifyAttribute:c(t,n._const.oldValue,n._const.newValue),l(e,t,n);break;case n._const.removeAttribute:t[n._const.action]=n._const.addAttribute,l(e,t,n);break;case n._const.modifyTextElement:case n._const.modifyValue:case n._const.modifyComment:case n._const.modifyChecked:case n._const.modifySelected:case n._const.replaceElement:c(t,n._const.oldValue,n._const.newValue),l(e,t,n);break;case n._const.relocateGroup:c(t,n._const.from,n._const.to),l(e,t,n);break;case n._const.removeElement:t[n._const.action]=n._const.addElement,l(e,t,n);break;case n._const.addElement:t[n._const.action]=n._const.removeElement,l(e,t,n);break;case n._const.removeTextElement:t[n._const.action]=n._const.addTextElement,l(e,t,n);break;case n._const.addTextElement:t[n._const.action]=n._const.removeTextElement,l(e,t,n);break;default:console.log("unknown action")}}(e,t,n)}))}var u=function(e){var t=[];return t.push(e.nodeName),"#text"!==e.nodeName&&"#comment"!==e.nodeName&&e.attributes&&(e.attributes.class&&t.push("".concat(e.nodeName,".").concat(e.attributes.class.replace(/ /g,"."))),e.attributes.id&&t.push("".concat(e.nodeName,"#").concat(e.attributes.id))),t},d=function(e){var t={},n={};return e.forEach((function(e){u(e).forEach((function(e){var o=e in t;o||e in n?o&&(delete t[e],n[e]=!0):t[e]=!0}))})),t},h=function(e,t){var n=d(e),o=d(t),s={};return Object.keys(n).forEach((function(e){o[e]&&(s[e]=!0)})),s},f=function(e){return delete e.outerDone,delete e.innerDone,delete e.valueDone,!e.childNodes||e.childNodes.every(f)},p=function(e){if(Object.prototype.hasOwnProperty.call(e,"data"))return{nodeName:"#text"===e.nodeName?"#text":"#comment",data:e.data};var n={nodeName:e.nodeName};return Object.prototype.hasOwnProperty.call(e,"attributes")&&(n.attributes=t({},e.attributes)),Object.prototype.hasOwnProperty.call(e,"checked")&&(n.checked=e.checked),Object.prototype.hasOwnProperty.call(e,"value")&&(n.value=e.value),Object.prototype.hasOwnProperty.call(e,"selected")&&(n.selected=e.selected),Object.prototype.hasOwnProperty.call(e,"childNodes")&&(n.childNodes=e.childNodes.map((function(e){return p(e)}))),n},m=function(e,t){if(!["nodeName","value","checked","selected","data"].every((function(n){return e[n]===t[n]})))return!1;if(Object.prototype.hasOwnProperty.call(e,"data"))return!0;if(Boolean(e.attributes)!==Boolean(t.attributes))return!1;if(Boolean(e.childNodes)!==Boolean(t.childNodes))return!1;if(e.attributes){var n=Object.keys(e.attributes),o=Object.keys(t.attributes);if(n.length!==o.length)return!1;if(!n.every((function(n){return e.attributes[n]===t.attributes[n]})))return!1}if(e.childNodes){if(e.childNodes.length!==t.childNodes.length)return!1;if(!e.childNodes.every((function(e,n){return m(e,t.childNodes[n])})))return!1}return!0},_=function(e,t,n,o,s){if(void 0===s&&(s=!1),!e||!t)return!1;if(e.nodeName!==t.nodeName)return!1;if(["#text","#comment"].includes(e.nodeName))return!!s||e.data===t.data;if(e.nodeName in n)return!0;if(e.attributes&&t.attributes){if(e.attributes.id){if(e.attributes.id!==t.attributes.id)return!1;if("".concat(e.nodeName,"#").concat(e.attributes.id)in n)return!0}if(e.attributes.class&&e.attributes.class===t.attributes.class)if("".concat(e.nodeName,".").concat(e.attributes.class.replace(/ /g,"."))in n)return!0}if(o)return!0;var i=e.childNodes?e.childNodes.slice().reverse():[],a=t.childNodes?t.childNodes.slice().reverse():[];if(i.length!==a.length)return!1;if(s)return i.every((function(e,t){return e.nodeName===a[t].nodeName}));var l=h(i,a);return i.every((function(e,t){return _(e,a[t],l,!0,!0)}))},v=function(e,t){return n([],new Array(e),!0).map((function(){return t}))},g=function(e,t){for(var o=e.childNodes?e.childNodes:[],s=t.childNodes?t.childNodes:[],i=v(o.length,!1),a=v(s.length,!1),l=[],c=function(){return arguments[1]},r=!1,d=function(){var e=function(e,t,o,s){var i=0,a=[],l=e.length,c=t.length,r=n([],new Array(l+1),!0).map((function(){return[]})),d=h(e,t),f=l===c;f&&e.some((function(e,n){var o=u(e),s=u(t[n]);return o.length!==s.length?(f=!1,!0):(o.some((function(e,t){if(e!==s[t])return f=!1,!0})),!f||void 0)}));for(var p=0;p<l;p++)for(var m=e[p],v=0;v<c;v++){var g=t[v];o[p]||s[v]||!_(m,g,d,f)?r[p+1][v+1]=0:(r[p+1][v+1]=r[p][v]?r[p][v]+1:1,r[p+1][v+1]>=i&&(i=r[p+1][v+1],a=[p+1,v+1]))}return 0!==i&&{oldValue:a[0]-i,newValue:a[1]-i,length:i}}(o,s,i,a);e?(l.push(e),n([],new Array(e.length),!0).map(c).forEach((function(t){return function(e,t,n,o){e[n.oldValue+o]=!0,t[n.newValue+o]=!0}(i,a,e,t)}))):r=!0};!r;)d();return e.subsets=l,e.subsetsAge=100,l},V=function(){function e(){this.list=[]}return e.prototype.add=function(e){var t;(t=this.list).push.apply(t,e)},e.prototype.forEach=function(e){this.list.forEach((function(t){return e(t)}))},e}();function b(e,t){var n,o,s=e;for(t=t.slice();t.length>0;)o=t.splice(0,1)[0],n=s,s=s.childNodes?s.childNodes[o]:void 0;return{node:s,parentNode:n,nodeIndex:o}}function N(e,t,n){return t.forEach((function(t){!function(e,t,n){var o,s,i,a;if(![n._const.addElement,n._const.addTextElement].includes(t[n._const.action])){var l=b(e,t[n._const.route]);s=l.node,i=l.parentNode,a=l.nodeIndex}var c,r,u=[],d={diff:t,node:s};if(n.preVirtualDiffApply(d))return!0;switch(t[n._const.action]){case n._const.addAttribute:s.attributes||(s.attributes={}),s.attributes[t[n._const.name]]=t[n._const.value],"checked"===t[n._const.name]?s.checked=!0:"selected"===t[n._const.name]?s.selected=!0:"INPUT"===s.nodeName&&"value"===t[n._const.name]&&(s.value=t[n._const.value]);break;case n._const.modifyAttribute:s.attributes[t[n._const.name]]=t[n._const.newValue];break;case n._const.removeAttribute:delete s.attributes[t[n._const.name]],0===Object.keys(s.attributes).length&&delete s.attributes,"checked"===t[n._const.name]?s.checked=!1:"selected"===t[n._const.name]?delete s.selected:"INPUT"===s.nodeName&&"value"===t[n._const.name]&&delete s.value;break;case n._const.modifyTextElement:s.data=t[n._const.newValue],"TEXTAREA"===i.nodeName&&(i.value=t[n._const.newValue]);break;case n._const.modifyValue:s.value=t[n._const.newValue];break;case n._const.modifyComment:s.data=t[n._const.newValue];break;case n._const.modifyChecked:s.checked=t[n._const.newValue];break;case n._const.modifySelected:s.selected=t[n._const.newValue];break;case n._const.replaceElement:c=p(t[n._const.newValue]),i.childNodes[a]=c;break;case n._const.relocateGroup:s.childNodes.splice(t[n._const.from],t[n._const.groupLength]).reverse().forEach((function(e){return s.childNodes.splice(t[n._const.to],0,e)})),s.subsets&&s.subsets.forEach((function(e){if(t[n._const.from]<t[n._const.to]&&e.oldValue<=t[n._const.to]&&e.oldValue>t[n._const.from])e.oldValue-=t[n._const.groupLength],(o=e.oldValue+e.length-t[n._const.to])>0&&(u.push({oldValue:t[n._const.to]+t[n._const.groupLength],newValue:e.newValue+e.length-o,length:o}),e.length-=o);else if(t[n._const.from]>t[n._const.to]&&e.oldValue>t[n._const.to]&&e.oldValue<t[n._const.from]){var o;e.oldValue+=t[n._const.groupLength],(o=e.oldValue+e.length-t[n._const.to])>0&&(u.push({oldValue:t[n._const.to]+t[n._const.groupLength],newValue:e.newValue+e.length-o,length:o}),e.length-=o)}else e.oldValue===t[n._const.from]&&(e.oldValue=t[n._const.to])}));break;case n._const.removeElement:i.childNodes.splice(a,1),i.subsets&&i.subsets.forEach((function(e){e.oldValue>a?e.oldValue-=1:e.oldValue===a?e.delete=!0:e.oldValue<a&&e.oldValue+e.length>a&&(e.oldValue+e.length-1===a?e.length--:(u.push({newValue:e.newValue+a-e.oldValue,oldValue:a,length:e.length-a+e.oldValue-1}),e.length=a-e.oldValue))})),s=i;break;case n._const.addElement:var h=(r=t[n._const.route].slice()).splice(r.length-1,1)[0];s=null===(o=b(e,r))||void 0===o?void 0:o.node,c=p(t[n._const.element]),s.childNodes||(s.childNodes=[]),h>=s.childNodes.length?s.childNodes.push(c):s.childNodes.splice(h,0,c),s.subsets&&s.subsets.forEach((function(e){if(e.oldValue>=h)e.oldValue+=1;else if(e.oldValue<h&&e.oldValue+e.length>h){var t=e.oldValue+e.length-h;u.push({newValue:e.newValue+e.length-t,oldValue:h+1,length:t}),e.length-=t}}));break;case n._const.removeTextElement:i.childNodes.splice(a,1),"TEXTAREA"===i.nodeName&&delete i.value,i.subsets&&i.subsets.forEach((function(e){e.oldValue>a?e.oldValue-=1:e.oldValue===a?e.delete=!0:e.oldValue<a&&e.oldValue+e.length>a&&(e.oldValue+e.length-1===a?e.length--:(u.push({newValue:e.newValue+a-e.oldValue,oldValue:a,length:e.length-a+e.oldValue-1}),e.length=a-e.oldValue))})),s=i;break;case n._const.addTextElement:var f=(r=t[n._const.route].slice()).splice(r.length-1,1)[0];c={nodeName:"#text",data:t[n._const.value]},(s=b(e,r).node).childNodes||(s.childNodes=[]),f>=s.childNodes.length?s.childNodes.push(c):s.childNodes.splice(f,0,c),"TEXTAREA"===s.nodeName&&(s.value=t[n._const.newValue]),s.subsets&&s.subsets.forEach((function(e){if(e.oldValue>=f&&(e.oldValue+=1),e.oldValue<f&&e.oldValue+e.length>f){var t=e.oldValue+e.length-f;u.push({newValue:e.newValue+e.length-t,oldValue:f+1,length:t}),e.length-=t}}));break;default:console.log("unknown action")}s.subsets&&(s.subsets=s.subsets.filter((function(e){return!e.delete&&e.oldValue!==e.newValue})),u.length&&(s.subsets=s.subsets.concat(u))),n.postVirtualDiffApply({node:d.node,diff:d.diff,newNode:c})}(e,t,n)})),!0}function y(e,t){void 0===t&&(t={valueDiffing:!0});var n={nodeName:e.nodeName};if(s(e,"Text","Comment"))n.data=e.data;else{if(e.attributes&&e.attributes.length>0)n.attributes={},Array.prototype.slice.call(e.attributes).forEach((function(e){return n.attributes[e.name]=e.value}));if(e.childNodes&&e.childNodes.length>0)n.childNodes=[],Array.prototype.slice.call(e.childNodes).forEach((function(e){return n.childNodes.push(y(e,t))}));t.valueDiffing&&(s(e,"HTMLTextAreaElement")&&(n.value=e.value),s(e,"HTMLInputElement")&&["radio","checkbox"].includes(e.type.toLowerCase())&&void 0!==e.checked?n.checked=e.checked:s(e,"HTMLButtonElement","HTMLDataElement","HTMLInputElement","HTMLLIElement","HTMLMeterElement","HTMLOptionElement","HTMLProgressElement","HTMLParamElement")&&(n.value=e.value),s(e,"HTMLOptionElement")&&(n.selected=e.selected))}return n}var w=/<\s*\/*[a-zA-Z:_][a-zA-Z0-9:_\-.]*\s*(?:"[^"]*"['"]*|'[^']*'['"]*|[^'"/>])*\/*\s*>|<!--(?:.|\n|\r)*?-->/g,E=/\s([^'"/\s><]+?)[\s/>]|([^\s=]+)=\s?(".*?"|'.*?')/g;function k(e){return e.replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/&amp;/g,"&")}var x={area:!0,base:!0,br:!0,col:!0,embed:!0,hr:!0,img:!0,input:!0,keygen:!0,link:!0,menuItem:!0,meta:!0,param:!0,source:!0,track:!0,wbr:!0},T=function(e,t){var n={nodeName:"",attributes:{}},o=!1,s=e.match(/<\/?([^\s]+?)[/\s>]/);if(s&&(n.nodeName=t||"svg"===s[1]?s[1]:s[1].toUpperCase(),(x[s[1]]||"/"===e.charAt(e.length-2))&&(o=!0),n.nodeName.startsWith("!--"))){var i=e.indexOf("--\x3e");return{type:"comment",node:{nodeName:"#comment",data:-1!==i?e.slice(4,i):""},voidElement:o}}for(var a=new RegExp(E),l=null,c=!1;!c;)if(null===(l=a.exec(e)))c=!0;else if(l[0].trim())if(l[1]){var r=l[1].trim(),u=[r,""];r.indexOf("=")>-1&&(u=r.split("=")),n.attributes[u[0]]=u[1],a.lastIndex--}else l[2]&&(n.attributes[l[2]]=l[3].trim().substring(1,l[3].length-1));return{type:"tag",node:n,voidElement:o}},O=function(e,t){void 0===t&&(t={valueDiffing:!0,caseSensitive:!1});var n,o=[],s=-1,i=[],a=!1;if(0!==e.indexOf("<")){var l=e.indexOf("<");o.push({nodeName:"#text",data:-1===l?e:e.substring(0,l)})}return e.replace(w,(function(l,c){var r="/"!==l.charAt(1),u=l.startsWith("\x3c!--"),d=c+l.length,h=e.charAt(d);if(u){var f=T(l,t.caseSensitive).node;if(s<0)return o.push(f),"";var p=i[s];return p&&f.nodeName&&(p.node.childNodes||(p.node.childNodes=[]),p.node.childNodes.push(f)),""}if(r){if("svg"===(n=T(l,t.caseSensitive||a)).node.nodeName&&(a=!0),s++,!n.voidElement&&h&&"<"!==h){n.node.childNodes||(n.node.childNodes=[]);var m=k(e.slice(d,e.indexOf("<",d)));n.node.childNodes.push({nodeName:"#text",data:m}),t.valueDiffing&&"TEXTAREA"===n.node.nodeName&&(n.node.value=m)}0===s&&n.node.nodeName&&o.push(n.node);var _=i[s-1];_&&n.node.nodeName&&(_.node.childNodes||(_.node.childNodes=[]),_.node.childNodes.push(n.node)),i[s]=n}if((!r||n.voidElement)&&(s>-1&&(n.voidElement||t.caseSensitive&&n.node.nodeName===l.slice(2,-1)||!t.caseSensitive&&n.node.nodeName.toUpperCase()===l.slice(2,-1).toUpperCase())&&--s>-1&&("svg"===n.node.nodeName&&(a=!1),n=i[s]),"<"!==h&&h)){var v=-1===s?o:i[s].node.childNodes||[],g=e.indexOf("<",d);m=k(e.slice(d,-1===g?void 0:g));v.push({nodeName:"#text",data:m})}return""})),o[0]},A=function(){function e(e,t,n){this.options=n,this.t1="undefined"!=typeof Element&&s(e,"Element")?y(e,this.options):"string"==typeof e?O(e,this.options):JSON.parse(JSON.stringify(e)),this.t2="undefined"!=typeof Element&&s(t,"Element")?y(t,this.options):"string"==typeof t?O(t,this.options):JSON.parse(JSON.stringify(t)),this.diffcount=0,this.foundAll=!1,this.debug&&(this.t1Orig="undefined"!=typeof Element&&s(e,"Element")?y(e,this.options):"string"==typeof e?O(e,this.options):JSON.parse(JSON.stringify(e)),this.t2Orig="undefined"!=typeof Element&&s(t,"Element")?y(t,this.options):"string"==typeof t?O(t,this.options):JSON.parse(JSON.stringify(t))),this.tracker=new V}return e.prototype.init=function(){return this.findDiffs(this.t1,this.t2)},e.prototype.findDiffs=function(e,t){var n;do{if(this.options.debug&&(this.diffcount+=1,this.diffcount>this.options.diffcap))throw new Error("surpassed diffcap:".concat(JSON.stringify(this.t1Orig)," -> ").concat(JSON.stringify(this.t2Orig)));0===(n=this.findNextDiff(e,t,[])).length&&(m(e,t)||(this.foundAll?console.error("Could not find remaining diffs!"):(this.foundAll=!0,f(e),n=this.findNextDiff(e,t,[])))),n.length>0&&(this.foundAll=!1,this.tracker.add(n),N(e,n,this.options))}while(n.length>0);return this.tracker.list},e.prototype.findNextDiff=function(e,t,n){var o,s;if(this.options.maxDepth&&n.length>this.options.maxDepth)return[];if(!e.outerDone){if(o=this.findOuterDiff(e,t,n),this.options.filterOuterDiff&&(s=this.options.filterOuterDiff(e,t,o))&&(o=s),o.length>0)return e.outerDone=!0,o;e.outerDone=!0}if(Object.prototype.hasOwnProperty.call(e,"data"))return[];if(!e.innerDone){if((o=this.findInnerDiff(e,t,n)).length>0)return o;e.innerDone=!0}if(this.options.valueDiffing&&!e.valueDone){if((o=this.findValueDiff(e,t,n)).length>0)return e.valueDone=!0,o;e.valueDone=!0}return[]},e.prototype.findOuterDiff=function(e,t,n){var s,i,a,l,c,r,u=[];if(e.nodeName!==t.nodeName){if(!n.length)throw new Error("Top level nodes have to be of the same kind.");return[(new o).setValue(this.options._const.action,this.options._const.replaceElement).setValue(this.options._const.oldValue,p(e)).setValue(this.options._const.newValue,p(t)).setValue(this.options._const.route,n)]}if(n.length&&this.options.diffcap<Math.abs((e.childNodes||[]).length-(t.childNodes||[]).length))return[(new o).setValue(this.options._const.action,this.options._const.replaceElement).setValue(this.options._const.oldValue,p(e)).setValue(this.options._const.newValue,p(t)).setValue(this.options._const.route,n)];if(Object.prototype.hasOwnProperty.call(e,"data")&&e.data!==t.data)return"#text"===e.nodeName?[(new o).setValue(this.options._const.action,this.options._const.modifyTextElement).setValue(this.options._const.route,n).setValue(this.options._const.oldValue,e.data).setValue(this.options._const.newValue,t.data)]:[(new o).setValue(this.options._const.action,this.options._const.modifyComment).setValue(this.options._const.route,n).setValue(this.options._const.oldValue,e.data).setValue(this.options._const.newValue,t.data)];for(i=e.attributes?Object.keys(e.attributes).sort():[],a=t.attributes?Object.keys(t.attributes).sort():[],l=i.length,r=0;r<l;r++)s=i[r],-1===(c=a.indexOf(s))?u.push((new o).setValue(this.options._const.action,this.options._const.removeAttribute).setValue(this.options._const.route,n).setValue(this.options._const.name,s).setValue(this.options._const.value,e.attributes[s])):(a.splice(c,1),e.attributes[s]!==t.attributes[s]&&u.push((new o).setValue(this.options._const.action,this.options._const.modifyAttribute).setValue(this.options._const.route,n).setValue(this.options._const.name,s).setValue(this.options._const.oldValue,e.attributes[s]).setValue(this.options._const.newValue,t.attributes[s])));for(l=a.length,r=0;r<l;r++)s=a[r],u.push((new o).setValue(this.options._const.action,this.options._const.addAttribute).setValue(this.options._const.route,n).setValue(this.options._const.name,s).setValue(this.options._const.value,t.attributes[s]));return u},e.prototype.findInnerDiff=function(e,t,n){var s=e.childNodes?e.childNodes.slice():[],i=t.childNodes?t.childNodes.slice():[],a=Math.max(s.length,i.length),l=Math.abs(s.length-i.length),c=[],r=0;if(!this.options.maxChildCount||a<this.options.maxChildCount){var u=Boolean(e.subsets&&e.subsetsAge--),d=u?e.subsets:e.childNodes&&t.childNodes?g(e,t):[];if(d.length>0&&(c=this.attemptGroupRelocation(e,t,d,n,u)).length>0)return c}for(var h=0;h<a;h+=1){var f=s[h],_=i[h];l&&(f&&!_?"#text"===f.nodeName?(c.push((new o).setValue(this.options._const.action,this.options._const.removeTextElement).setValue(this.options._const.route,n.concat(r)).setValue(this.options._const.value,f.data)),r-=1):(c.push((new o).setValue(this.options._const.action,this.options._const.removeElement).setValue(this.options._const.route,n.concat(r)).setValue(this.options._const.element,p(f))),r-=1):_&&!f&&("#text"===_.nodeName?c.push((new o).setValue(this.options._const.action,this.options._const.addTextElement).setValue(this.options._const.route,n.concat(r)).setValue(this.options._const.value,_.data)):c.push((new o).setValue(this.options._const.action,this.options._const.addElement).setValue(this.options._const.route,n.concat(r)).setValue(this.options._const.element,p(_))))),f&&_&&(!this.options.maxChildCount||a<this.options.maxChildCount?c=c.concat(this.findNextDiff(f,_,n.concat(r))):m(f,_)||(s.length>i.length?("#text"===f.nodeName?c.push((new o).setValue(this.options._const.action,this.options._const.removeTextElement).setValue(this.options._const.route,n.concat(r)).setValue(this.options._const.value,f.data)):c.push((new o).setValue(this.options._const.action,this.options._const.removeElement).setValue(this.options._const.element,p(f)).setValue(this.options._const.route,n.concat(r))),s.splice(h,1),h-=1,r-=1,l-=1):s.length<i.length?(c=c.concat([(new o).setValue(this.options._const.action,this.options._const.addElement).setValue(this.options._const.element,p(_)).setValue(this.options._const.route,n.concat(r))]),s.splice(h,0,p(_)),l-=1):c=c.concat([(new o).setValue(this.options._const.action,this.options._const.replaceElement).setValue(this.options._const.oldValue,p(f)).setValue(this.options._const.newValue,p(_)).setValue(this.options._const.route,n.concat(r))]))),r+=1}return e.innerDone=!0,c},e.prototype.attemptGroupRelocation=function(e,t,n,s,i){for(var a,l,c,r,u,d=function(e,t,n){var o=e.childNodes?v(e.childNodes.length,!0):[],s=t.childNodes?v(t.childNodes.length,!0):[],i=0;return n.forEach((function(e){for(var t=e.oldValue+e.length,n=e.newValue+e.length,a=e.oldValue;a<t;a+=1)o[a]=i;for(a=e.newValue;a<n;a+=1)s[a]=i;i+=1})),{gaps1:o,gaps2:s}}(e,t,n),h=d.gaps1,f=d.gaps2,m=e.childNodes.slice(),g=t.childNodes.slice(),V=Math.min(h.length,f.length),b=[],N=0,y=0;N<V;y+=1,N+=1)if(!i||!0!==h[N]&&!0!==f[N]){if(!0===h[y])if("#text"===(r=m[y]).nodeName)if("#text"===g[N].nodeName){if(r.data!==g[N].data){for(var w=y;m.length>w+1&&"#text"===m[w+1].nodeName;)if(w+=1,g[N].data===m[w].data){u=!0;break}u||b.push((new o).setValue(this.options._const.action,this.options._const.modifyTextElement).setValue(this.options._const.route,s.concat(y)).setValue(this.options._const.oldValue,r.data).setValue(this.options._const.newValue,g[N].data))}}else b.push((new o).setValue(this.options._const.action,this.options._const.removeTextElement).setValue(this.options._const.route,s.concat(y)).setValue(this.options._const.value,r.data)),h.splice(y,1),m.splice(y,1),V=Math.min(h.length,f.length),y-=1,N-=1;else!0===f[N]?b.push((new o).setValue(this.options._const.action,this.options._const.replaceElement).setValue(this.options._const.oldValue,p(r)).setValue(this.options._const.newValue,p(g[N])).setValue(this.options._const.route,s.concat(y))):(b.push((new o).setValue(this.options._const.action,this.options._const.removeElement).setValue(this.options._const.route,s.concat(y)).setValue(this.options._const.element,p(r))),h.splice(y,1),m.splice(y,1),V=Math.min(h.length,f.length),y-=1,N-=1);else if(!0===f[N])"#text"===(r=g[N]).nodeName?(b.push((new o).setValue(this.options._const.action,this.options._const.addTextElement).setValue(this.options._const.route,s.concat(y)).setValue(this.options._const.value,r.data)),h.splice(y,0,!0),m.splice(y,0,{nodeName:"#text",data:r.data}),V=Math.min(h.length,f.length)):(b.push((new o).setValue(this.options._const.action,this.options._const.addElement).setValue(this.options._const.route,s.concat(y)).setValue(this.options._const.element,p(r))),h.splice(y,0,!0),m.splice(y,0,p(r)),V=Math.min(h.length,f.length));else if(h[y]!==f[N]){if(b.length>0)return b;if(c=n[h[y]],(l=Math.min(c.newValue,m.length-c.length))!==c.oldValue){a=!1;for(var E=0;E<c.length;E+=1)_(m[l+E],m[c.oldValue+E],{},!1,!0)||(a=!0);if(a)return[(new o).setValue(this.options._const.action,this.options._const.relocateGroup).setValue(this.options._const.groupLength,c.length).setValue(this.options._const.from,c.oldValue).setValue(this.options._const.to,l).setValue(this.options._const.route,s)]}}}else;return b},e.prototype.findValueDiff=function(e,t,n){var s=[];return e.selected!==t.selected&&s.push((new o).setValue(this.options._const.action,this.options._const.modifySelected).setValue(this.options._const.oldValue,e.selected).setValue(this.options._const.newValue,t.selected).setValue(this.options._const.route,n)),(e.value||t.value)&&e.value!==t.value&&"OPTION"!==e.nodeName&&s.push((new o).setValue(this.options._const.action,this.options._const.modifyValue).setValue(this.options._const.oldValue,e.value||"").setValue(this.options._const.newValue,t.value||"").setValue(this.options._const.route,n)),e.checked!==t.checked&&s.push((new o).setValue(this.options._const.action,this.options._const.modifyChecked).setValue(this.options._const.oldValue,e.checked).setValue(this.options._const.newValue,t.checked).setValue(this.options._const.route,n)),s},e}(),D={debug:!1,diffcap:10,maxDepth:!1,maxChildCount:50,valueDiffing:!0,textDiff:function(e,t,n,o){e.data=o},preVirtualDiffApply:function(){},postVirtualDiffApply:function(){},preDiffApply:function(){},postDiffApply:function(){},filterOuterDiff:null,compress:!1,_const:!1,document:!("undefined"==typeof window||!window.document)&&window.document,components:[]},L=function(){function e(e){if(void 0===e&&(e={}),Object.entries(D).forEach((function(t){var n=t[0],o=t[1];Object.prototype.hasOwnProperty.call(e,n)||(e[n]=o)})),!e._const){var t=["addAttribute","modifyAttribute","removeAttribute","modifyTextElement","relocateGroup","removeElement","addElement","removeTextElement","addTextElement","replaceElement","modifyValue","modifyChecked","modifySelected","modifyComment","action","route","oldValue","newValue","element","group","groupLength","from","to","name","value","data","attributes","nodeName","childNodes","checked","selected"],n={};e.compress?t.forEach((function(e,t){return n[e]=t})):t.forEach((function(e){return n[e]=e})),e._const=n}this.options=e}return e.prototype.apply=function(e,t){return function(e,t,n){return t.every((function(t){return l(e,t,n)}))}(e,t,this.options)},e.prototype.undo=function(e,t){return r(e,t,this.options)},e.prototype.diff=function(e,t){return new A(e,t,this.options).init()},e}(),M=function(){function e(e){void 0===e&&(e={});var t=this;this.pad="│   ",this.padding="",this.tick=1,this.messages=[];var n=function(e,n){var o=e[n];e[n]=function(){for(var s=arguments,i=[],a=0;a<arguments.length;a++)i[a]=s[a];t.fin(n,Array.prototype.slice.call(i));var l=o.apply(e,i);return t.fout(n,l),l}};for(var o in e)"function"==typeof e[o]&&n(e,o);this.log("┌ TRACELOG START")}return e.prototype.fin=function(e,t){this.padding+=this.pad,this.log("├─> entering ".concat(e),t)},e.prototype.fout=function(e,t){this.log("│<──┘ generated return value",t),this.padding=this.padding.substring(0,this.padding.length-this.pad.length)},e.prototype.format=function(e,t){return"".concat(function(e){for(var t="".concat(e);t.length<4;)t="0".concat(e);return t}(t),"> ").concat(this.padding).concat(e)},e.prototype.log=function(){for(var e=arguments,t=[],n=0;n<arguments.length;n++)t[n]=e[n];var o=function(e){return e?"string"==typeof e?e:s(e,"HTMLElement")?e.outerHTML||"<empty>":e instanceof Array?"[".concat(e.map(o).join(","),"]"):e.toString()||e.valueOf()||"<unknown>":"<falsey>"},i=t.map(o).join(", ");this.messages.push(this.format(i,this.tick++))},e.prototype.toString=function(){for(var e="└───";e.length<=this.padding.length+this.pad.length;)e+="×   ";var t=this.padding;return this.padding="",e=this.format(e,this.tick),this.padding=t,"".concat(this.messages.join("\n"),"\n").concat(e)},e}();return e.DiffDOM=L,e.TraceLogger=M,e.nodeToObj=y,e.stringToObj=O,e}({});
    //# sourceMappingURL=diffDOM.js.map
</script>

<!--suppress JSDeprecatedSymbols -->
<script>
    const createLock = () =>
    {
        let lockStatus = false
        const release = () =>
        {
            lockStatus = false
        }
        const acuire = () =>
        {
            if (lockStatus === true)
                return false
            lockStatus = true
            return true
        }
        return {
            lockStatus: lockStatus,
            acuire: acuire,
            release: release,
        }
    }
    lock = createLock(); // create a lock
    var globalReload = 0;
    var loader = null;
    var loaderContainer = null;

    function ReloadViewAll(preload = 200)
    {
        ReloadViewBefore();

        ShowLoader(preload);

        var elementi = document.querySelectorAll('[id^="View"]');
        globalReload = elementi.length;
        for (var i = 0; i < elementi.length; i++)
        {
            let viewId = elementi[i].id.replace(/view/gi, ""); //case sensitive
            ReloadViewInternal(viewId);
        }
    }

    function ReloadViewBefore()
    {

    }

    function ReloadViewCompleted()
    {

    }

    function ReloadView(viewName, preload = 200)
    {
        ReloadViewBefore();

        ShowLoader(preload);

        let fullName = "View\\" + viewName;

        let view;

        var elementi = document.querySelectorAll('[id^="View"]');

        for (var i = 0; i < elementi.length; i++)
        {
            let viewTmp = elementi[i];

            let name = viewTmp.getAttribute("view");

            if (name !== fullName)
                continue;

            view = viewTmp;

            break;
        }

        if (typeof view === 'undefined')
        {
            console.log("Non trovo la view con nome " + viewName);
            return;
        }

        let viewId = elementi[i].id.replace(/view/gi, ""); //case sensitive

        ReloadViewInternal(viewId);
    }

    function ReloadViewInternal(viewId)
    {
        const call = async () =>
        {
            var divName = "View" + viewId;
            var div = document.getElementById(divName);
            if (div == null)
            {
                console.log("Non trovo la view nel div " + divName);
                return;
            }

            let viewTag = div.getAttribute("view");

            if (viewTag == null)
            {
                console.log("Non trovo il tag view nel div " + divName);
                return;
            }

            //KeepSave(div);

            var hidden = document.getElementById("WindowState");
            let array = [];
            array.push(""); //tempState
            if (hidden)
            {
                try
                {
                    array.push(hidden.value);
                }
                catch (e)
                {
                }
            }

            const response = await fetch('/Public/Php/Common/View/Client.php?view=' + viewTag,
            {
                method: 'POST',
                headers:
                    {
                        'Content-Type': 'application/json'
                    },
                body: JSON.stringify(array)
            });

            let nuovo = div.cloneNode(false);
            nuovo.innerHTML = await response.text();

            let dd = new diffDOM.DiffDOM({
                valueDiffing: false,
            });

            let diff = dd.diff(div, nuovo);

            dd.apply(div, diff);

            //KeepRestore(div);

            globalReload--;
            if (globalReload <= 0)
            {
                ReloadViewCompleted();
                HideLoader();
            }
        }

        call();
    }

    // function KeepRestore(view)
    // {
    //     var jsonMap = WindowRead("KeepView" + view.id);
    //
    //     if (!jsonMap)
    //         return; // Se non c'è alcun valore, esci dalla funzione
    //
    //     var map = new Map(JSON.parse(jsonMap)); // Converti il JSON in una mappa
    //
    //     for (var [id, value] of map.entries())
    //     {
    //         var element = document.getElementById(id); // Ottieni l'elemento corrispondente all'ID dalla mappa
    //
    //         if (!element)
    //             continue; // Se l'elemento non è trovato, continua con il prossimo
    //
    //         switch (element.nodeName)
    //         {
    //             case 'INPUT':
    //                 switch (element.type)
    //                 {
    //                     case 'text':
    //                     case 'date':
    //                     case 'tel':
    //                     case 'email':
    //                     case 'hidden':
    //                     case 'password':
    //                     case 'button':
    //                     case 'reset':
    //                     case 'submit':
    //                         element.value = value; // Imposta il valore dell'elemento
    //                         break;
    //                     case 'checkbox':
    //                     case 'radio':
    //                         if (element.value === value)
    //                         {
    //                             element.checked = true; // Se il valore coincide, segna l'elemento come selezionato
    //                         }
    //                         break;
    //                 }
    //                 break;
    //             case 'TEXTAREA':
    //                 element.value = value;
    //                 break;
    //             case 'SELECT':
    //                 switch (element.type)
    //                 {
    //                     case 'select-one':
    //                         // Imposta il valore solo se è presente nelle opzioni
    //                         if ([...element.options].some(option => option.value === value)) {
    //                             element.value = value;
    //                         }
    //                         break;
    //                     case 'select-multiple':
    //                         var selectedOptions = value;
    //                         for (var j = 0; j < element.options.length; j++)
    //                         {
    //                             element.options[j].selected = selectedOptions.includes(element.options[j].value);
    //                         }
    //                         break;
    //                 }
    //                 break;
    //             case 'BUTTON':
    //                 switch (element.type)
    //                 {
    //                     case 'reset':
    //                     case 'submit':
    //                     case 'button':
    //                         element.value = value;
    //                         break;
    //                 }
    //                 break;
    //         }
    //     }
    // }
    //
    // function KeepSave(view)
    // {
    //     var map = new Map();
    //
    //     var elements = view.querySelectorAll('[class*="Keep"]');
    //
    //     for (var i = elements.length - 1; i >= 0; i--)
    //     {
    //         var element = elements[i];
    //
    //         if (element.nodeType !== 1)
    //             continue; // Skip non-element nodes
    //
    //         if (element.id)
    //         {
    //             var id = element.id;
    //
    //             switch (element.nodeName)
    //             {
    //                 case 'INPUT':
    //                     switch (element.type)
    //                     {
    //                         case 'text':
    //                         case 'date':
    //                         case 'tel':
    //                         case 'email':
    //                         case 'hidden':
    //                         case 'password':
    //                         case 'button':
    //                         case 'reset':
    //                         case 'submit':
    //                             map.set(id, element.value);
    //                             break;
    //                         case 'checkbox':
    //                         case 'radio':
    //                             if (element.checked)
    //                                 map.set(id, element.value);
    //                             break;
    //                     }
    //                     break;
    //                 case 'TEXTAREA':
    //                     map.set(id, element.value);
    //                     break;
    //                 case 'SELECT':
    //                     switch (element.type)
    //                     {
    //                         case 'select-one':
    //                             map.set(id, element.value);
    //                             break;
    //                         case 'select-multiple':
    //                             var selectedValues = [];
    //                             for (var j = element.options.length - 1; j >= 0; j--)
    //                             {
    //                                 if (element.options[j].selected)
    //                                 {
    //                                     selectedValues.push(element.options[j].value);
    //                                 }
    //                             }
    //                             map.set(id, selectedValues);
    //                             break;
    //                     }
    //                     break;
    //                 case 'BUTTON':
    //                     switch (element.type)
    //                     {
    //                         case 'reset':
    //                         case 'submit':
    //                         case 'button':
    //                             map.set(id, element.value);
    //                             break;
    //                     }
    //                     break;
    //             }
    //         }
    //     }
    //
    //     var jsonMap = JSON.stringify([...map.entries()]);
    //     WindowWrite("KeepView" + view.id, jsonMap);
    // }

    function Push(nome, valore)
    {
        if (typeof window[nome] === "function")
        {
            if (valore === undefined || valore === null)
            {
                window[nome]();
            } else
            {
                try
                {
                    // Prova a convertire la stringa JSON in un array
                    var arrayValore = JSON.parse(valore);

                    if (Array.isArray(arrayValore))
                    {
                        window[nome].apply(null, arrayValore);
                    } else
                    {
                        window[nome](valore); // Se non è un array, passa come singolo parametro
                    }
                } catch (error)
                {
                    window[nome](valore); // Se c'è un errore, passa come singolo parametro
                }
            }
        } else
        {
            console.error("PUSH: La funzione " + nome + " non esiste o non è una funzione.");
        }
    }

    let timeoutPreloader;

    function ShowLoader(preload)
    {
        if (loaderContainer != null || loader != null)
            return;

        if (preload < 0)
            preload = 0;

        clearTimeout(timeoutPreloader);

        timeoutPreloader = setTimeout(function ()
        {
            loaderContainer = document.createElement("div");
            loaderContainer.classList.add("loaderContainer");

            loader = document.createElement("div");
            loader.classList.add("loader");

            loaderContainer.appendChild(loader);

            document.body.appendChild(loaderContainer);

        }, preload);
    }

    function HideLoader()
    {
        clearTimeout(timeoutPreloader);

        if (loaderContainer == null || loader == null)
            return;

        loaderContainer.remove();

        loaderContainer = null;
        loader = null;
    }

    function Action(controller, action, result)
    {
        if (!lock.acuire()) // acuired a lock
            return;
        var windowState = document.getElementById("WindowState");
        var windowJson = "";
        if (windowState)
        {
            try
            {
                windowJson = windowState.value;
            } catch (e)
            {
            }
        }
        var tempState = document.getElementById("TempState");
        var tempJson = "";
        if (tempState)
        {
            try
            {
                tempJson = tempState.value;
            } catch (e)
            {
            }
        }
        let finalArray = [tempJson, windowJson];
        fetch('/Public/Php/Common/View/Client.php?controller=' + controller + '&action=' + action,
            {
                method: 'POST',
                headers:
                    {
                        'Content-Type': 'application/json'
                    },
                body: JSON.stringify(finalArray)
            })
            .then(data =>
            {
                data.text().then(output =>
                {
                    let jsonArray = JSON.parse(output);
                    document.getElementById("TempState").value = jsonArray[0];
                    document.getElementById("WindowState").value = jsonArray[1];
                    lock.release();

                    if (typeof result === "function")
                        result();
                });
            })
            .catch(error =>
            {
                console.log('Network error:', error);
            });
    }

    function WindowWrite(name, value)
    {
        if (typeof name === "undefined")
        {
            console.log("WindowWrite: name è undefined");
            return;
        }
        if (typeof value === "undefined")
        {
            console.log("WindowWrite: value è undefined");
            return;
        }
        name = name.toLowerCase();

        let windowState = document.getElementById("WindowState");
        var json = windowState.value;
        var jsonArray = {};
        if (json)
        {
            try
            {
                json = decodeURIComponent(escape(atob(json)));
                jsonArray = JSON.parse(json);
            } catch (e)
            {
            }
        }
        jsonArray[name] = value.toString();
        json = JSON.stringify(jsonArray);
        windowState.value = btoa(unescape(encodeURIComponent(json)));
    }

    function WindowRead(name)
    {
        if (typeof name === "undefined")
        {
            console.log("WindowRead: name è undefined");
            return;
        }
        name = name.toLowerCase();
        var json = document.getElementById("WindowState").value;
        var jsonArray = [];
        if (json)
        {
            try
            {
                json = decodeURIComponent(escape(atob(json)));
                jsonArray = JSON.parse(json);
            } catch (e)
            {
            }
        }
        let res = jsonArray[name];
        if (typeof res === 'undefined')
            return "";
        return res;
    }

    function TempClear()
    {
        document.getElementById("TempState").value = "";
    }

    function TempWrite(name, value)
    {
        if (typeof name === "undefined")
        {
            console.log("TempWrite: name è undefined");
            return;
        }
        if (typeof value === "undefined")
        {
            console.log("TempWrite: value è undefined");
            return;
        }
        name = name.toLowerCase();
        var json = document.getElementById("TempState").value;
        var jsonArray = {};
        if (json)
        {
            try
            {
                json = decodeURIComponent(escape(atob(json)));
                jsonArray = JSON.parse(json);
            } catch (e)
            {
                console.log(e);
            }
        }
        jsonArray[name] = value.toString();
        json = JSON.stringify(jsonArray);
        json = btoa(unescape(encodeURIComponent(json)));
        document.getElementById("TempState").value = json;
    }

    function TempRead(name)
    {
        if (typeof name === "undefined")
        {
            console.log("TempRead: name è undefined");
            return;
        }
        name = name.toLowerCase();
        var json = document.getElementById("TempState").value;
        var jsonArray = [];
        if (json)
        {
            try
            {
                json = decodeURIComponent(escape(atob(json)));
                jsonArray = JSON.parse(json);
            } catch (e)
            {
                return e;
            }
        }
        let res = jsonArray[name];
        if (typeof res === 'undefined')
            return "";
        return res;
    }

    function TempWriteAllId()
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');
        postInputs.forEach(function (input)
        {
            var id = input.id;
            var value;
            if (input.type === 'checkbox')
            {
                value = input.checked ? 'true' : 'false';
            } else if (input.tagName.toLowerCase() === 'select')
            {
                var selectedOption = input.options[input.selectedIndex];
                value = selectedOption.value;
            } else
            {
                value = input.value;
            }

            TempWrite(id, value);
        });
    }

    function getOffset(el) {
        const rect = el.getBoundingClientRect();
        return {
            left: rect.left + window.scrollX,
            top: rect.top + window.scrollY
        };
    }

    function TempReadAllId(message, scroll = false)
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');

        var labelDanger = []

        postInputs.forEach(function (input)
        {
            let id = input.id;
            let value = TempRead(id);
            let label = document.querySelector('label[for="' + id + '"]');
            if (value.trim() !== '')
            {
                if (!label)
                {
                    label = document.createElement('label');
                    label.setAttribute('for', id);
                    label.classList.add('danger');
                    input.parentNode.insertBefore(label, input.nextSibling);

                }
                label.innerHTML = value;

                labelDanger.push(label);

            } else if (label)
            {
                label.parentNode.removeChild(label);
            }
        });

        if (message !== '')
        {
            let parser = new DOMParser();
            alert(parser.parseFromString(message, 'text/html').documentElement.textContent);
        }

        if (scroll)
        {
            //altezza di default, non faccio una mazza se quando arrivo in fondo il valore è ancora questo
            let labelHeight = -1;

            labelDanger.forEach(function (label)
            {
                let input = document.getElementById(label.attributes.getNamedItem("for").value);

                let top = getOffset(input).top - 10;

                if ((labelHeight == -1) || (labelHeight > top))
                    labelHeight = top;
            });

            if (labelHeight !== -1)
                window.scrollTo(0, labelHeight);
        }
    }

    // Funzione per salvare lo stato dei valori degli input
    function WindowWriteAllId()
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');
        postInputs.forEach(function (input)
        {
            var id = input.id;
            var value;
            if (input.type === 'checkbox')
            {
                value = input.checked ? 'true' : 'false';
            } else if (input.tagName.toLowerCase() === 'select')
            {
                var selectedOption = input.options[input.selectedIndex];
                value = selectedOption.value;
            } else
            {
                value = input.value;
            }
            WindowWrite(id, value);
        });
    }
</script>