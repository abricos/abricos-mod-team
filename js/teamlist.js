/* 
@package Abricos
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
		R = NS.roles;

	var buildTemplate = this.buildTemplate;
	
	var TeamListRowWidget = function(container, team, cfg){
		cfg = L.merge({
			'desctrim': 300
		}, cfg || {});
		this.init(container, team, cfg);
	};
	TeamListRowWidget.prototype = {
		buildTemplate: function(){
			return buildTemplate(this, 'row,img');
		},
		urlView: function(){
			return '';
			// return NS.navigator.team.view(this.team.id);
		},
		init: function(container, team, cfg){
			this.team = team;
			this.cfg = cfg;

			var TM = this.buildTemplate(this);
			
			container.innerHTML += TM.replace('row', {
				'id': team.id,
				'cnt': 1,
				'tl': team.title,
				'dsc': this.trimDescript(team.descript),
				'mbrcnt': team.memberCount,
				'urlview': this.urlView(),
				'img': L.isNull(team.logo) ? '' : TM.replace('img', {
					'urlview': this.urlView(),
					'fid': team.logo,
					'tl': team.title
				})
			});
		},
		destroy: function(){
			var el = this._TM.getEl('row.id');
			el.parentNode.removeChild(el);
		},
		trimDescript: function(s){
			var cfg = this.cfg;
			s = s.replace(/\<br\/\>/gi, ' ');
			if (cfg['desctrim'] > 0 && s.length > cfg['desctrim']){
				s = s.substring(0, cfg['desctrim']) +"...";
			}
			return s;
		}
	};
	NS.TeamListRowWidget = TeamListRowWidget;
	
	var TeamListWidget = function(container, list){
		this.init(container, list);
	};
	TeamListWidget.overrides = {};
	
	TeamListWidget.prototype = {
		init: function(container, list){
			this.list = list;
			
			this._wList = [];
			
			var TM = buildTemplate(this, 'widget,empty');
			container.innerHTML = TM.replace('widget');
			
			var __self = this;
			E.on(container, 'click', function(e){
				var el = E.getTarget(e);
				if (__self.onClick(el)){ E.preventDefault(e); }
			});
			this.render();
		},
		destroy: function(){
			this._clearWS();
			var el = this._TM.getEl('widget.id');
			el.parentNode.removeChild(el);
		},
		_clearWS: function(){
			var ws = this._wList;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this._wList = [];
		},
		onClick: function(el){
			return false;
		},
		render: function(){
			this._clearWS();
			
			var TM = this._TM, gel = function(n){ return TM.getEl('widget.'+n);};
			var ws = this._wList;
			
			this.list.foreach(function(team){
				var wRowClass = NS.TeamListWidget.overrides[team.module];
				if (wRowClass){
					ws[ws.length] = new wRowClass(gel('list'), team);
				}else{
					ws[ws.length] = new NS.TeamListRowWidget(gel('list'), team);
				}
				
			});
		}
	};
	NS.TeamListWidget = TeamListWidget;
	

};