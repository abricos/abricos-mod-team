/* 
@package Abricos
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[{name: 'user', files: ['permission.js']}]
};
Component.entryPoint = function(){
	
	var NS = this.namespace,
		BP = Brick.Permission,
		mn = this.moduleName;

	NS.roles = {
		load: function(callback){
			BP.load(function(){
				var r = NS.roles;
				r['isAdmin'] = BP.check(mn, '50') == 1;
				r['isWrite'] = BP.check(mn, '30') == 1;
				r['isView'] = BP.check(mn, '10') == 1;
				callback();
			});
		}
	};
	
};