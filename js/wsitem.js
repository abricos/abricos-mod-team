/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: '{C#MODNAME}', files: ['lib.js']}
	]		
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		R = NS.roles,
		LNG = this.language;

	var buildTemplate = this.buildTemplate;

	var WSItemPanel = function(NSMod, teamid, pgInfo, cfg){
		this.NSMod = NSMod;
		this.teamid = teamid;
		this.pgInfo = pgInfo || [];
		this.cfg = L.merge({
			'modtitle': LNG.get('modtitle')
		}, cfg || {});
		
		WSItemPanel.superclass.constructor.call(this, {
			fixedcenter: true, width: '790px', height: '400px'
		});
	};
	YAHOO.extend(WSItemPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'panel,img,menuitem');
			
			return this._TM.replace('panel', {
				'modtitle': this.cfg['modtitle']
			});
		},
		onLoad: function(){
			this.team = null;
			this.widget = null;
			var __self = this, NSMod = this.NSMod, teamid = this.teamid;
			
			
			NSMod.initManager(function(man){
				NSMod.manager.teamLoad(teamid, function(team){
					__self._onLoadManager(man, team);
				});
			});
		},
		destroy: function(){},
		_onLoadManager: function(man, team){
	
			this.team = team;
			var TM = this._TM, gel = function(n){ return TM.getEl('panel.'+n); };
			Dom.setStyle(gel('gloading'), 'display', 'none');
			if (L.isNull(team)){
				Dom.setStyle(gel('error'), 'display', '');
				return;
			}
			
			var lst = "";
			
			man.initData.appInfoList.foreach(function(app){
				if (app.parentName != ''){ return; }
				lst += TM.replace('menuitem', {
					'id': app.id,
					'tl': app.title,
					'url': "#app="+man.modname+"/wsitem/wsi/"+team.id+'/'+app.moduleName+'/'+app.name+'/'+app.widgetName+'/'
				});
			});
			gel('topmenu').innerHTML += lst;
			
			gel('title').innerHTML = team.title;
			if (!L.isNull(team.logo)){
				gel('logo').innerHTML = TM.replace('img', {
					'fid': team.logo
				});
			}
			
			Dom.setStyle(gel('cont'), 'display', '');
			this.showPage(this.pgInfo);
		},
		showPage: function(p){
			p = L.merge({
				'm': '', 'c': '', 'w': '',
				'p1': '', 'p2': '', 'p3': '', 'p4': '', 'p5': ''
			}, p || {});

			var appList = this.NSMod.manager.initData.appInfoList,
				app = appList.getBy(p['m'], p['c']);
			if (!L.isValue(app) && !Brick.componentExists(p['m'], p['c'])){
				app = appList.getByIndex(0);
				p['m'] = app.moduleName;
				p['c'] = app.name;
				p['w'] = app.widgetName;
			}
			
			var __self = this, TM = this._TM, TId = this._TId, gel = function(n){ return TM.getEl('panel.'+n); };

			var sapp = app;
			if (L.isValue(app) && app.parentName != ''){
				sapp = appList.getBy(app.moduleName, app.parentName);
			}

			appList.foreach(function(fapp){
				var miEl = Dom.get(TId['menuitem']['id']+'-'+fapp.id);
				if (!L.isValue(miEl)){ return; }
				
				if (L.isValue(sapp) && sapp.id == fapp.id){
					Dom.addClass(miEl, 'sel');
				}else{
					Dom.removeClass(miEl, 'sel');
				}
			});
			
			Dom.setStyle(gel('board'), 'display', 'none');
			Dom.setStyle(gel('loading'), 'display', '');

			Brick.ff(p['m'], p['c'], function(){
				Dom.setStyle(gel('board'), 'display', '');
				Dom.setStyle(gel('loading'), 'display', 'none');
				
				var appNS = Brick.mod[p['m']];
				if (!appNS[p['w']]){ return; }
				
				var widget = __self.widget;
				if (!L.isNull(widget)){
					widget.destroy();
				}
				gel('board').innerHTMl = "";
				__self.widget = new appNS[p['w']](gel('board'), __self.teamid, p['p1'], p['p2'], p['p3'], p['p4'], p['p5']);
			});
		}
	});
	NS.WSItemPanel = WSItemPanel;

};