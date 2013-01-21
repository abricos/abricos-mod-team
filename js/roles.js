/*
@version $Id: roles.js 881 2012-07-17 07:34:26Z roosit $
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
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