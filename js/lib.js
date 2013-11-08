/* 
@package Abricos
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'sys', files: ['item.js']},
        {name: 'widget', files: ['notice.js']},
        {name: 'uprofile', files: ['lib.js']},
        {name: '{C#MODNAME}', files: ['roles.js']}
	]
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang;

	var SysNS = Brick.mod.sys;
	var UP = Brick.mod.uprofile;

	this.buildTemplate({}, '');
	
	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f,p1,p2,p3,p4,p5,p6,p7){
		f=NS.lif(f); f(p1,p2,p3,p4,p5,p6,p7);
	};
	NS.Item = SysNS.Item;
	NS.ItemList = SysNS.ItemList;
	
	var AppInfo = function(d){
		d = L.merge({
			'mnm': '',
			'nm': '',
			'w': '',
			'tl': '',
			'pnm': ''
		}, d || {});
		AppInfo.superclass.constructor.call(this, d);
	};
	YAHOO.extend(AppInfo, SysNS.Item, {
		update: function(d){
			this.moduleName = d['mnm'];
			this.name = d['nm'];
			this.widgetName = d['w'];
			this.title = d['tl'];
			this.parentName = d['pnm'];
		}
	});
	NS.AppInfo = AppInfo;
	
	var AppInfoList = function(d){
		AppInfoList.superclass.constructor.call(this, d, AppInfo);
	};
	YAHOO.extend(AppInfoList, SysNS.ItemList, {
		getBy: function(mname, cname){
			var ret = null;
			this.foreach(function(app){
				if (app.moduleName == mname
						&& app.name == cname){ 
					ret = app;
					return true;
				}
			});
			return ret;
		}
	});
	NS.AppInfoList = AppInfoList;
	
	var TypeInfo = function(d){
		d = L.merge({
			'nm': '',
			'tnm': '',
			'tl': ''
		}, d || {});
		TypeInfo.superclass.constructor.call(this, d);
	};
	YAHOO.extend(TypeInfo, SysNS.Item, {
		update: function(d){
			this.name = d['nm'];
			this.teamModName = d['tnm'];
			this.title = d['tl'];
		}
	});
	NS.TypeInfo = TypeInfo;

	var TypeInfoList = function(d){
		TypeInfoList.superclass.constructor.call(this, d, TypeInfo);
	};
	YAHOO.extend(TypeInfoList, SysNS.ItemList, {});
	NS.TypeInfoList = TypeInfoList;
	
	var InitData = function(d){
		d = L.merge({
			'apps': []
		}, d || {});
		this.init(d);
	};
	InitData.prototype = {
		init: function(d){
			this.appInfoList = new NS.AppInfoList(d['apps']);
			this.typeInfoList = new NS.TypeInfoList(d['types']);
		}
	};
	NS.InitData = InitData;
	
	var _TMODULENAMECACHE = {};

	var Team = function(d, man){
		d = L.merge({
			'id': 0,
			'm': '{C#MODNAME}',
			'pm': '',
			'tp': '',
			'nm': '',
			'eml': '',
			'tl': '',
			'dsc': '',
			'site': '',
			'logo': '',
			'mcnt': 0,
			'auid': Brick.env.user.id,
			'role': {}
		}, d || {});

		this.manager = man || Manager.get(d['m']);

		Team.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Team, SysNS.Item, {
		init: function(d){
			this.role = new this.manager['TeamUserRoleClass'](d['role']);
			this.navigator = new this.manager['NavigatorClass'](this);
			this.extended = new NS.TeamExtendedManager(this);
			this.detail = null;
			
			Team.superclass.init.call(this, d);
		},
		update: function(d){
			
			this.module		= d['m'];
			this.parentModule = d['pm'];
			this.type		= d['tp'];
			this.name		= d['nm'];
			this.authorid	= d['auid'];			// создатель сообщества
			this.title		= d['tl'];
			this.descript	= d['dsc'];
			this.site		= d['site'];
			this.email		= d['eml'];
			this.logo		= d['logo'].length == 8 ? d['logo'] : null;
			this.memberCount = d['mcnt']*1;

			this.siteHTML = this.siteURL = '';
			if (L.isString(this.site) && this.site.length > 3){
				this.siteURL = 'http://'+this.site;
				this.siteHTML = "<a href='"+this.siteURL+"' target='_blank'>"+this.site+"</a>";
			}
			this.role.update(d['role']);
			
			if (L.isValue(d['dtl'])){
				this.detail = new this.manager.TeamDetailClass(d['dtl']);
			}
			
			if (this.id > 0){
				_TMODULENAMECACHE[this.id|0] = this.module;
			}
		}
	});
	NS.Team = Team;
	
	var TeamExtendedManager = function(team){
		this.init(team);
	};
	TeamExtendedManager.prototype = {
		init: function(team){
			this.team = team;
			this.cache = {};
		},
		load: function(modName, appName, callback){
			var cache = this.cache, team = this.team;

			if (L.isArray(modName)){
				// загрузка данных нескольких модулей рекурсивно из массива
				var m = modName.pop();
				team.extended.load(m, appName, function(appData){
					if (modName.length>0){
						team.extended.load(modName, appName, callback);
					}else{
						NS.life(callback, cache[m][appName]);
					}
				});
				return;
			}
			
			cache[modName] = cache[modName] || {};
			var extData = cache[modName][appName];
			if (L.isValue(extData)){
				NS.life(callback, extData);
				return;
			}
			
			NS.app.load(modName, appName, function(appManager){
				if (!L.isValue(appManager)){
					NS.life(callback, null);
				}else{
					appManager.teamExtendedDataLoad(team, function(extData){
						if (!L.isValue(extData)){
							NS.life(callback, null);
						}else{
							cache[modName][appName] = extData;
							NS.life(callback, extData);
						}
					});
				}
			});
		},
		get: function(modName, appName){
			var cache = this.cache;
			cache[modName] = cache[modName] || {};
			
			return cache[modName][appName] || null;
		},
		foreach: function(f){
			var list = this.cache;
			for (var m in list){
				for (var appName in list[m]){
					NS.life(f, list[m][appName]);
				}
			}
		}
	};
	NS.TeamExtendedManager = TeamExtendedManager;
	
	var TeamList = function(d, teamClass){
		TeamList.superclass.constructor.call(this, d, teamClass || Team);
	};
	YAHOO.extend(TeamList, SysNS.ItemList, {});
	NS.TeamList = TeamList;
	
	var TeamDetail = function(d){
		d = L.merge({
			// 'iwCount': 0
		}, d || {});
		this.init(d);
	};
	TeamDetail.prototype = {
		init: function(d){
			this.update(d);
		},
		update: function(d){
			// this.inviteWaitCount = d['iwCount'];
		}
	};
	NS.TeamDetail = TeamDetail;

	var TeamUserRole = function(d){
		d = L.merge({
			'ismbr': 0,
			'isadm': 0,
			'isinv': 0,
			'isjrq': 0,
			'isvrt': 0,
			'ruid': 0
		}, d || {});
		this.init(d);
	};
	TeamUserRole.prototype = {
		init: function(d){
			this.update(d);
		},
		update: function(d){
			this.isMember = (d['ismbr']|0)==1;
			this.isAdmin = (d['isadm']|0)==1;
			this.isVirtual = (d['isvrt']|0)==1;
			
			this.isJoinRequest = !this.isMember && (d['isjrq']|0)==1;
			this.isInvite = !this.isMember && (d['isinv']|0)==1;
			this.relUserId = d['ruid']|0;
		}
	};
	NS.TeamUserRole = TeamUserRole;
	
	var Navigator = function(team){
		this.init(team);
	};
	Navigator.prototype = {
		init: function(team){
			this.team = team;
		},
		URI: function(){
			var m = this.team.module;
			if (this.team.parentModule.length > 0){
				m = this.team.parentModule;
			}
			return "#app="+m+'/wsitem/wsi/'+this.team.id+'/';
		}
		/*,
		memberListURI: function(){
			var man = this.team.manager;
			return this.URI()+man.modName+'/memberlist/GroupListWidget/';
		},
		memberViewURI: function(memberid){
			var man = this.team.manager;
			return this.URI()+man.modName+'/memberview/MemberViewWidget/'+memberid+'/';
		}
		/**/
	};
	NS.Navigator = Navigator;
	
	var TeamAppInitData = function(manager, d){
		this.init(manager, d);
	};
	TeamAppInitData.prototype = {
		init: function(manager, d){
			this.manager = manager;
			this.update(d);
		},
		update: function(d){ }
	};
	NS.TeamAppInitData = TeamAppInitData;
	
	var TeamExtendedData = function(team, manager, d){
		this.init(team, manager, d);
	};
	TeamExtendedData.prototype = {
		init: function(team, manager, d){
			this.id = team.id;
			this.team = team;
			this.manager = manager;
			this.update(d);
		},
		update: function(d){}
	};
	NS.TeamExtendedData = TeamExtendedData;
	
	var TeamAppManager = function(modName, appName, callback, cfg){
		this.modName = modName;
		this.appName = appName;
		
		cfg = L.merge({
			'TeamExtendedDataClass': TeamExtendedData,
			'InitDataClass': TeamAppInitData
		}, cfg || {});
		
		this.init(callback, cfg);
	};
	TeamAppManager.prototype = {
		init: function(callback, cfg){
			this.cfg = cfg;
			
			this.users = UP.viewer.users;
			this.TeamExtendedDataClass	= cfg['TeamExtendedDataClass'];
			this.InitDataClass			= cfg['InitDataClass'];
			
			this.initData = null;
			
			this._cacheRelatedModuleList = null;
		},
		ajax: function(d, callback){
			d = d || {};
			d['tm'] = Math.round((new Date().getTime())/1000);
			
			if (!L.isValue(this.initData)){
				d['initdata'] = true;
			}
			
			var __self = this;
			Brick.ajax(this.modName, {
				'data': d,
				'event': function(request){
					var d = L.isValue(request) && L.isValue(request.data) ? request.data : null,
						result = L.isValue(d) ? (d.result ? d.result : null) : null;
					
					if (L.isValue(d)){
						if (L.isValue(d['initdata'])){
							__self.initData = new __self.InitDataClass(__self, d['initdata']);
						}
						if (L.isValue(d['users'])){
							__self.users.update(d['users']);
						}
					}
					NS.life(callback, result);
				}
			});
		},
		teamExtendedDataLoad: function(team, callback){
			var __self = this;
			this.ajax({
				'do': 'teamextendeddata',
				'teamid': team.id
			}, function(d){
				if (L.isValue(d) && L.isValue(d['teamextendeddata'])){
					var extData = new __self.TeamExtendedDataClass(team, __self, d['teamextendeddata']);
					NS.life(callback, extData);
				}else{
					NS.life(callback, null);
				}
			});
		},
		// список всех родственных приложений
		// например для любого из приложений teammember список может быть employee, sportsman и т.п.
		relatedModuleNameList: function(team, callback){
			if (L.isValue(this._cacheRelatedModuleList)){
				NS.life(callback, this._cacheRelatedModuleList);
			}
			
			var __self = this, related = [];
			
			this.ajax({
				'do': 'relatedmodulelist',
				'teamid': team.id
			}, function(d){
				
				if (L.isValue(d) && L.isArray(d['relatedmodules'])){
					__self._cacheRelatedModuleList = 
						related = d['relatedmodules'];
				}
				
				NS.life(callback, related);
			});			
		}
	};
	NS.TeamAppManager = TeamAppManager;
	
	var Manager = function(modName, callback, cfg){
		this.modName = modName;
		cfg = L.merge({
			'InitDataClass':		InitData,
			'TeamClass':			Team,
			'TeamDetailClass':		TeamDetail,
			'TeamListClass':		TeamList,
			'TeamUserRoleClass':	TeamUserRole,
			'NavigatorClass':		Navigator
		}, cfg || {});
		
		// специализированный виджеты в перегруженном модуле
		cfg['teamEditor'] = L.merge({
			'module': '{C#MODNAME}',
			'component': 'teameditor',
			'widget': 'TeamEditorWidget'
		}, cfg['teamEditor'] || {});
		
		cfg['teamRemove'] = L.merge({
			'module': '{C#MODNAME}',
			'component': 'teameditor',
			'panel': 'TeamRemovePanel'
		}, cfg['memberEditor'] || {});

		this.init(callback, cfg);
	};
	Manager.prototype = {
		init: function(callback, cfg){
			
			this.cfg = cfg;

			this.InitDataClass		= cfg['InitDataClass'];
			
			this.TeamClass			= cfg['TeamClass'];
			this.TeamDetailClass	= cfg['TeamDetailClass'];
			this.TeamListClass		= cfg['TeamListClass'];
			this.TeamUserRoleClass	= cfg['TeamUserRoleClass'];
			
			this.NavigatorClass		= cfg['NavigatorClass'];

			this._cacheTeam = {};
			
			this.initData = null;
			
			// при очередном запросе данных подгрузить еще и список 
			// всех участников сообщества, если он не подгружен ранее
			// this.requestGlobalMemberList = false;
		},
		
		ajax: function(req, callback){
			req = req || {};
			req['tm'] = Math.round((new Date().getTime())/1000);
			
			if (!L.isValue(this.initData)){
				req['initdataupdate'] = true;
			}
			
			var __self = this;
			Brick.ajax(this.modName, {
				'data': req,
				'event': function(request){
					var d = L.isValue(request) && L.isValue(request.data) ? request.data : null,
						result = L.isValue(d) ? (d.result ? d.result : null) : null;
		
					if (L.isValue(d)){
						if (L.isValue(d['initdata'])){
							__self.initData = new __self.InitDataClass(d['initdata']);
						}
					}
					if (L.isArray(d['log'])){
						Brick.console(d['log']);
					}
					NS.life(callback, result);
				}
			});
		},
		
		_updateTeamList: function(d, callback){
			if (!d || !d['teams'] || !L.isArray(d['teams']['list'])){
				NS.life(callback, null);
				return;
			}
			
			var dList = d['teams']['list'];
			
			var list = new this.TeamListClass();

			if (dList.length == 0){
				NS.life(callback, list);
				return;
			}
			
			var oMans = {};
			// собрать список всех менеджеров модулей
			for (var i=0;i<dList.length;i++){
				var m = dList[i]['m'];
				oMans[m] = true;
			}
			var aMans = [];
			for (var m in oMans){
				aMans[aMans.length] = m;
			}
			
			// подгрузить менеджеры сообществ
			NS.Manager.init(aMans, function(){
				// создать список сообществ
				for (var i=0;i<dList.length;i++){
					var di = dList[i], man = NS.Manager.get(di['m']);
					if (!L.isValue(man)){ continue; }
					
					var team = man._cacheTeam[di['id']];
					
					if (!L.isValue(team)){
						team = new man.TeamClass(di);
					}else{
						team.update(di);
					}
					list.add(team);
				}
				NS.life(callback, list);
			});
		},
		
		teamListLoad: function(callback, prm){
			prm = L.merge({
				'page': 1,
				'memberid': 0
			}, prm||{});
			prm['do'] = 'teamlist';
			
			var __self = this;
			
			this.ajax(prm, function(d){
				__self._updateTeamList(d, function(list){
					NS.life(callback, list);
				});
			});
		},
		
		teamSave: function(sd, callback){
			var __self = this;
			sd['do'] = 'teamsave';
			
			this.ajax(sd, function(d){
				__self._updateTeamList(d, function(list){
					NS.life(callback, list);
				});
			});
		},
		
		teamLoad: function(teamid, callback, cfg){
			cfg = L.merge({
				'reload': false,
				'other': null
			}, cfg || {});
			
			var __self = this,
				team = this._cacheTeam[teamid];

			if (L.isValue(team) && L.isValue(team.detail) && !cfg['reload']){
				NS.life(callback, team);
				return;
			}
			
			var rq = {
				'do': 'team',
				'teamid': teamid
			};
			if (!L.isNull(cfg['other'])){
				rq['other'] = cfg['other'];
			}
			this.ajax(rq, function(d){
				if (d && !L.isNull(d) && d['team']){
					if (!L.isValue(team)){
						team = new __self.TeamClass(d['team']);
						__self._cacheTeam[teamid] = team;
					}else{
						team.update(d['team']);
					}
				}
				NS.life(callback, team);
			});
		},
		
		teamRemove: function(team, callback){
			if (L.isNull(team)){
				NS.life(callback);
				return;
			}
			this.ajax({'do': 'teamremove', 'teamid': team.id}, function(d){
				NS.life(callback);
			});
		},
		
		/*
		_updateMemberList: function(team, d){
			this._updateGroupList(team, d);
			this._updateMemberInGroupList(team, d);
			
			if (!L.isValue(d) || !L.isValue(d['members']) || !L.isArray(d['members']['list'])){
				return null;
			}
				
			var list = team.memberList = new this.MemberListClass();
			
			var dList = d['members']['list'];
			for (var i=0; i<dList.length; i++){
				list.add(new this.MemberClass(team, dList[i]));
			}
			return list;
		},
		
		memberListLoad: function(team, callback){
			if (L.isNull(team)){
				NS.life(callback, null);
				return;
			}
			// запросить весь список участников сообещства
			this.requestGlobalMemberList = true;
			var __self = this;
			this.ajax({
				'do': 'memberlist',
				'teamid': team.id
			}, function(d){
				var list = __self._updateMemberList(team, d);
				NS.life(callback, list);
			});
		},
		
		_updateMember: function(team, d){
			this._updateMemberList(team, d);
			if (!L.isValue(d) || !L.isValue(d['member']) || (d['member']['id']|0) == 0){
				return null;
			}
			var memberid = d['member']['id']|0,
				member = team.memberList.get(memberid);

			if (L.isValue(member)){
				member.update(d['member']);
			}
			
			return member;
		},
		
		memberLoad: function(team, memberid, callback){
			if (L.isNull(team)){
				NS.life(callback, null);
				return;
			}
			var sDo = 'member';
			if (!L.isValue(team.memberList)){
				sDo += '|memberlist';
			}
			var __self = this;
			this.ajax({
				'do': sDo,
				'teamid': team.id,
				'memberid': memberid
			}, function(d){
				var member = __self._updateMember(team, d);

				NS.life(callback, member);
			});
		},
		
		memberSave: function(team, sd, callback){
			this.requestGlobalMemberList = true;

			var __self = this;
			this.ajax({
				'do': 'membersave',
				'teamid': team.id,
				'savedata': sd
			}, function(d){
				var member = __self._updateMember(team, d);
				NS.life(callback, member);
			});
		},
		
		memberInviteAccept: function(team, sd, callback){
			this.requestGlobalMemberList = true;
			
			var __self = this;
			sd['do'] = 'memberinviteact';
			this.ajax(sd, function(d){
				var member = __self._updateMember(team, d);
				NS.life(callback, member);
			});
		},
		
		memberRemove: function(team, member, callback){
			this.requestGlobalMemberList = true;
			
			var __self = this;
			this.ajax({
				'do': 'memberremove', 
				'teamid': team.id,
				'memberid': member.id
			}, function(d){
				__self._updateMemberList(team, d);
				NS.life(callback);
			});
		}
		/**/
	};
	Manager.cache = {};
	Manager.init = function(modName, callback){
		if (L.isArray(modName)){
			// инициализация менеджеров рекурсивно из массива
			var m = modName.pop();
			Manager.init(m, function(){
				if (modName.length>0){
					Manager.init(modName, callback);
				}else{
					NS.life(callback, Manager.cache[m]);
				}
			});
			return;
		}
		
		if (L.isValue(Manager.cache[modName])){
			NS.life(callback, Manager.cache[modName]);
			return;
		}
		
		Brick.ff(modName, 'lib', function(){
			var NSMod = Brick.mod[modName];
			if (L.isValue(NSMod)){
				new NSMod.Manager(function(man){
					Manager.cache[modName] = man; 
					NS.life(callback, man);
				});
			}else{
				NS.life(callback, null);
			}
		});
	};	
	Manager.get = function(modName){
		return Manager.cache[modName];
	};
	NS.Manager = Manager;

	NS.teamLoad = function(teamid, callback, cfg){
		cfg = L.merge({
			'modName': null
		}, cfg || {});
		
		var _teamLoad = function(mName){
			Manager.init(mName, function(man){
				if (L.isValue(man)){
					man.teamLoad(teamid, function(team){
						NS.life(callback, team, man);
					});
				}else{
					NS.life(callback, null, null);
				}
			});
		};
		
		if (L.isString(cfg['modName']) && cfg['modName'].length > 0){
			_teamLoad(cfg['modName']);
		}else if (L.isValue(_TMODULENAMECACHE[teamid])){
			_teamLoad(_TMODULENAMECACHE[teamid]);
		}else{
			Brick.ajax('team', {
				'data': {
					'do': 'teammodulename',
					'teamid': teamid
				},
				'event': function(request){
					if (L.isValue(request) && L.isValue(request.data)){
						_teamLoad(request.data);
					}else{
						NS.life(callback, null, null);
					}
				}
			});		
		}
	};
	
	NS.teamAppDataLoad = function(teamid, modName, appName, callback){
		NS.teamLoad(teamid, function(team){
			if (!L.isValue(team)){
				NS.life(callback, null);
			}else{
				team.extended.load(modName, appName, function(appData){
					NS.life(callback, appData);
				});
			}
		});
	};
	
	var AppManager = function(){
		this.init();
	};
	AppManager.prototype = {
		init: function(){
			this.classes = {};
			this.list = {};
		},
		loadClass: function(modName, appName, callback){
			var cs = this.classes;
			cs[modName] = cs[modName] || {};
			
			var manClass = this.getClass(modName, appName);
			if (L.isValue(manClass)){
				NS.life(callback, manClass);
			}else{
				var __self = this;
				Brick.ff(modName, 'lib', function(){
					manClass = __self.getClass(modName, appName);
					NS.life(callback, manClass);
				});
			}
		},
		load: function(modName, appName, callback){
			var list = this.list;
			
			if (L.isArray(modName)){
				// инициализация менеджеров рекурсивно из массива
				var m = modName.pop();
				NS.app.load(m, appName, function(man){
					if (modName.length>0){
						NS.app.load(modName, appName, callback);
					}else{
						NS.life(callback, list[m][appName]);
					}
				});
				return;
			}
			
			list[modName] = list[modName] || {};
			

			var man = list[modName][appName];
			if (L.isValue(man)){
				NS.life(callback, man);
			}else{
				this.loadClass(modName, appName, function(manClass){
					if (!L.isValue(manClass)){
						NS.life(callback, null);
					}else{
						list[modName][appName] = new manClass(function(man){
							list[modName][appName] = man;
							NS.life(callback, man);
						});
					}
				});
			}
		},
		register: function(modName, appName, manClass){
			var cs = this.classes;
			cs[modName] = cs[modName] || {};
			cs[modName][appName] = manClass;
		},
		getClass: function(modName, appName){
			return this.classes[modName][appName];
		},
		foreach: function(f){
			var list = this.list;
			for (var m in list){
				for (var appName in list[m]){
					NS.life(f, list[m][appName]);
				}
			}
		},
		get: function(modName, appName){
			var list = this.list;
			list[modName] = list[modName] || {};
			return list[modName][appName] || null;
		}
	};
	
	NS.app = new AppManager();
};