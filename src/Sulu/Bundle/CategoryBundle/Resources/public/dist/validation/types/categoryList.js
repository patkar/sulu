define(["type/default"],function(a){"use strict";var b=function(){App.emit("sulu.content.changed")};return function(c,d){var e={},f={initializeSub:function(){App.off("husky.datagrid.categories.item.select",b),App.on("husky.datagrid.categories.item.select",b),App.off("husky.datagrid.categories.item.deselect",b),App.on("husky.datagrid.categories.item.deselect",b)},setValue:function(a){App.dom.data(c,"selected",a)},getValue:function(){return App.dom.data(c,"selected")},needsValidation:function(){return!1},validate:function(){return!0}};return new a(c,e,d,"categoryList",f)}});