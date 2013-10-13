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
		L = YAHOO.lang,
		R = NS.roles;

	var CE = YAHOO.util.CustomEvent;
	var SysNS = Brick.mod.sys;
	var LNG = this.language;
	var UP = Brick.mod.uprofile;

	this.buildTemplate({}, '');
	
	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f,p1,p2,p3,p4,p5,p6,p7){
		f=NS.lif(f); f(p1,p2,p3,p4,p5,p6,p7);
	};
	NS.Item = SysNS.Item;
	NS.ItemList = SysNS.ItemList;
	
	NS.emailValidate = function(email) { 
		var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	    return re.test(email);
	};
	
	// глобальный кеш сообществ
	// NS.teamCache = {};
	
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
	YAHOO.extend(TypeInfoList, SysNS.ItemList, {
		
	});
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

	var Team = function(d, man){
		d = L.merge({
			'id': 0,
			'm': 'team',
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
			this.detail = null;
			
			this.memberList = null;
			this.memberGroupList = null;
			this.memberInGroup = null;
			
			Team.superclass.init.call(this, d);
		},
		update: function(d){
			
			this.module		= d['m'];
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
			
			if (d['dtl'] && !L.isNull(d['dtl'])){
				this.detail = new this.manager['TeamDetailClass'](d['dtl']);
			}
		}
		/*,
		load: function(callback, reload){ // загрузить полные данные
			NS.Manager.fire(this.module, 'teamLoad', this.id, callback, reload);
		}
		/**/
	});
	NS.Team = Team;
	
	// все участники сообещства
	NS.Team.globalMemberList = function(){
		
		var cache = {};
		
		return {
			get: function(teamid){
				if (!L.isValue(cache[teamid])){
					cache[teamid] = null;
				}
				return cache[teamid];
			},
			set: function(teamid, memberList){
				cache[teamid] = memberList;
			}
		};
		
	}();
	
	var TeamList = function(d, teamClass){
		TeamList.superclass.constructor.call(this, d, teamClass || Team);
	};
	YAHOO.extend(TeamList, SysNS.ItemList, {});
	NS.TeamList = TeamList;
	
	var TeamDetail = function(d){
		d = L.merge({
			'iwCount': 0
		}, d || {});
		this.init(d);
	};
	TeamDetail.prototype = {
		init: function(d){
			this.update(d);
		},
		update: function(d){
			this.inviteWaitCount = d['iwCount'];
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
			this.isModMember = this.isMember;
			this.isAdmin = (d['isadm']|0)==1;
			this.isVirtual = (d['isvrt']|0)==1;
			
			this.isJoinRequest = !this.isMember && (d['isjrq']|0)==1;
			this.isInvite = !this.isMember && (d['isinv']|0)==1;
			this.relUserId = d['ruid']|0;
		}
	};
	NS.TeamUserRole = TeamUserRole;
	
	var MemberGroup = function(d){
		d = L.merge({
			'tl': '',
			'pid': 0
		}, d || {});
		MemberGroup.superclass.constructor.call(this, d);
	};
	YAHOO.extend(MemberGroup, SysNS.Item, {
		update: function(d){
			this.parentid = d['pid']|0;
			this.title = d['tl'];
		}
	});
	NS.MemberGroup = MemberGroup;

	var MemberGroupList = function(d){
		MemberGroupList.superclass.constructor.call(this, d, MemberGroup);
	};
	YAHOO.extend(MemberGroupList, SysNS.ItemList, {});
	NS.MemberGroupList = MemberGroupList;
	
	var MemberInGroup = function(d){
		d = L.merge({
			'gid': 0,
			'uid': 0
		}, d || {});
		MemberInGroup.superclass.constructor.call(this, d);
	};
	YAHOO.extend(MemberInGroup, SysNS.Item, {
		update: function(d){
			this.groupid = d['gid']|0;
			this.memberid = d['uid']|0;
		}
	});
	NS.MemberInGroup = MemberInGroup;

	var MemberInGroupList = function(d){
		MemberInGroupList.superclass.constructor.call(this, d, MemberInGroup);
	};
	YAHOO.extend(MemberInGroupList, SysNS.ItemList, {
		getMemberGroupId: function(memberid){
			var groupid = 0;
			this.foreach(function(mig){
				if (mig.memberid == memberid){
					groupid = mig.groupid;
					return true;
				}
			});
			return groupid;
		},
		checkMemberInGroup: function(memberid, groupid){
			var mgid = this.getMemberGroupId(memberid);
			return mgid == groupid;
		}
	});
	NS.MemberInGroupList = MemberInGroupList;

	var Member = function(team, d){
		this.team = team;
		d = L.merge({
			'role': {}
		}, d || {});
		Member.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Member, SysNS.Item, {
		init: function(d){
			Member.superclass.init.call(this, d);
		},
		update: function(d){
			if (L.isValue(this.team)){
				this.role = new this.team.manager.TeamUserRoleClass(d['role']);
			}else{
				this.role = new NS.TeamUserRole(d['role']);
			}
		}
	});
	NS.Member = Member;
	
	var MemberList = function(d){
		MemberList.superclass.constructor.call(this, d, Member);
	};
	YAHOO.extend(MemberList, SysNS.ItemList, {
		createItem: function(di){
			return new NS.Member(null, di);
		}
	});
	NS.MemberList = MemberList;
	
	var UserConfig = function(d){
		d = L.merge({
			'iwCount': 0,
			'iwLimit': 0
		}, d || {});
		this.init(d);
	};
	UserConfig.prototype = {
		init: function(d){
			this.needUpdate = true;
			this.update(d);
		},
		update: function(d){
			this.inviteWaitCount = d['iwCount'];
			this.inviteWaitLimit = d['iwLimit'];
		}
	};
	NS.UserConfig = UserConfig;
	
	var Navigator = function(team){
		this.init(team);
	};
	Navigator.prototype = {
		init: function(team){
			this.team = team;
		},
		URI: function(){
			return "#app="+this.team.module+'/wsitem/wsi/'+this.team.id+'/';
		}
	};
	NS.Navigator = Navigator;

	var Manager = function(modname, callback, cfg){
		this.modname = modname;
		cfg = L.merge({
			'InitDataClass':		InitData,
			'UserConfigClass':		UserConfig,
			'TeamClass':			Team,
			'TeamDetailClass':		TeamDetail,
			'TeamListClass':		TeamList,
			'TeamUserRoleClass':	TeamUserRole,
			'MemberClass':			Member,
			'MemberListClass':		MemberList,
			'NavigatorClass':		Navigator
		}, cfg || {});
		
		// специализированный виджеты в перегруженном модуле
		cfg['memberGroupEditor'] = L.merge({
			'module': 'team',
			'component': 'mgroupeditor',
			'widget': 'MemberGroupEditorWidget'
		}, cfg['memberGroupEditor'] || {});

		cfg['memberEditor'] = L.merge({
			'module': 'team',
			'component': 'membereditor',
			'widget': 'MemberEditorWidget'
		}, cfg['memberEditor'] || {});
		
		cfg['memberRemove'] = L.merge({
			'module': 'team',
			'component': 'membereditor',
			'panel': 'MemberRemovePanel'
		}, cfg['memberEditor'] || {});

		this.init(callback, cfg);
	};
	Manager.prototype = {
		init: function(callback, cfg){
			
			this.cfg = cfg;

			this.InitDataClass		= cfg['InitDataClass'];
			this.UserConfigClass	= cfg['UserConfigClass'];
			
			this.TeamClass			= cfg['TeamClass'];
			this.TeamDetailClass	= cfg['TeamDetailClass'];
			this.TeamListClass		= cfg['TeamListClass'];
			this.TeamUserRoleClass	= cfg['TeamUserRoleClass'];
			
			this.MemberClass		= cfg['MemberClass'];
			this.MemberListClass	= cfg['MemberListClass'];
			this.NavigatorClass		= cfg['NavigatorClass'];

			this.invite = null;
			this.userConfig = new this.UserConfigClass();
			this._cacheTeam = {};
			
			this.users = UP.viewer.users;
			
			this.initData = null;
			
			// при очередном запросе данных подгрузить еще и список 
			// всех участников сообщества, если он не подгружен ранее
			this.requestGlobalMemberList = false;
		},
		
		ajax: function(req, callback){
			req = req || {};
			req['tm'] = Math.round((new Date().getTime())/1000);
			
			var userConfig = this.userConfig;
			if (userConfig.needUpdate){
				req['userconfigupdate'] = true;
				userConfig.needUpdate = false;
			}
			if (!L.isValue(this.initData)){
				req['initdataupdate'] = true;
			}
			
			if (this.requestGlobalMemberList && req['teamid']>0 
					&& !L.isValue(NS.Team.globalMemberList.get(req['teamid']))){
				req['globalmemberlist'] = true;
			}
			
			var __self = this;
			Brick.ajax(this.modname, {
				'data': req,
				'event': function(request){
					var d = L.isValue(request) && L.isValue(request.data) ? request.data : null,
						result = L.isValue(d) ? (d.result ? d.result : null) : null;
		
					if (L.isValue(d)){
						if (L.isValue(d['userconfig'])){
							userConfig.update(d['userconfig']);
						}
						if (L.isValue(d['users'])){
							__self.users.update(d['users']);
						}
						if (L.isValue(d['initdata'])){
							__self.initData = new __self.InitDataClass(d['initdata']);
						}
						if (req['teamid'] && L.isValue(d['globalmemberlist']) && L.isArray(d['globalmemberlist']['list'])){
							var list = new NS.MemberList(d['globalmemberlist']['list']);
							NS.Team.globalMemberList.set(req['teamid'], list);
						}
					}
					if (L.isArray(d['log'])){
						Brick.console(d['log']);
					}
					NS.life(callback, result);
				}
			});
		},
		
		_updateTeamList: function(d){
			if (!d || !d['teams'] || !L.isArray(d['teams']['list'])){ 
				return null; 
			}
			
			var dList = d['teams']['list'];
			
			var list = new this.TeamListClass();
			
			for (var i=0;i<dList.length;i++){
				var di = dList[i], team = this._cacheTeam[di['id']];
				
				if (!L.isValue(team)){
					team = new this.TeamClass(di);
				}else{
					team.update(di);
				}
				list.add(team);
			}
			return list;
		},
		
		teamListLoad: function(callback, prm){
			prm = L.merge({
				'page': 1,
				'memberid': 0
			}, prm||{});
			prm['do'] = 'teamlist';
			
			var __self = this;
			
			this.ajax(prm, function(d){
				var list = __self._updateTeamList(d);
				NS.life(callback, list);
			});
		},
		
		teamSave: function(sd, callback){
			var __self = this;
			sd['do'] = 'teamsave';
			
			this.ajax(sd, function(d){
				var list = __self._updateTeamList(d);
				NS.life(callback, list);
			});
		},
		
		teamLoad: function(teamid, callback, cfg){
			cfg = L.merge({
				'reload': false,
				'other': null
			}, cfg || {});
			
			var __self = this,
				team = this._cacheTeam[teamid];

			if (L.isValue(team) && !L.isNull(team.detail) && !cfg['reload']){
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
		
		_updateMemberGroupList: function(team, d){
			if (!L.isValue(d) || !L.isValue(d['membergroups']) || !L.isArray(d['membergroups']['list'])){
				return null;
			}
				
			var list = team.memberGroupList = new NS.MemberGroupList();
			
			var dList = d['membergroups']['list'];
			for (var i=0; i<dList.length; i++){
				list.add(new NS.MemberGroup(dList[i]));
			}
			return list;
		},

		_updateMemberInGroupList: function(team, d){
			if (!L.isValue(d) || !L.isValue(d['memberingroups']) || !L.isArray(d['memberingroups']['list'])){
				return null;
			}
				
			var list = team.memberInGroupList = new NS.MemberInGroupList();
			
			var dList = d['memberingroups']['list'];
			for (var i=0; i<dList.length; i++){
				list.add(new NS.MemberInGroup(dList[i]));
			}
			return list;
		},
		
		_updateMemberList: function(team, d){
			this._updateMemberGroupList(team, d);
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
		
		memberGroupSave: function(team, sd, callback){
			var __self = this;
			this.ajax({
				'do': 'membergroupsave',
				'teamid': team.id,
				'savedata': sd
			}, function(d){
				__self._updateMemberGroupList(team, d);
				var group = null;
				if (L.isValue(d) && d['groupid'] > 0){
					group = team.memberGroupList.get(d['groupid']);
				}
				NS.life(callback, group);
			});
		},

		memberGroupRemove: function(team, groupid, callback){
			var __self = this;
			this.ajax({
				'do': 'membergroupremove',
				'teamid': team.id,
				'groupid': groupid
			}, function(d){
				__self._updateMemberGroupList(team, d);
				NS.life(callback);
			});
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
			if (!L.isValue(d) || !L.isValue(d['member']) || d['member']['id']|0 == 0){
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
				'do': 'member',
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
	};
	// Получить менеджер наследуемого приложения
	Manager.get = function(modname){
		var man = Brick.mod[modname]['manager'];
		if (!L.isObject(man)){
			man = NS.manager;
		}
		return man;
	};
	Manager.fire = function(modname, fname, p1, p2, p3, p4, p5, p6, p7, p8){
		var func = Manager.get(modname)[fname];
		if (L.isFunction(func)){
			func(p1, p2, p3, p4, p5, p6, p7, p8);
		}
	};
	NS.Manager = Manager;

	NS.getTeam = function(teamid, callback){
		/*
		var team = NS.teamCache[teamid];
		
		if (L.isValue(team)){
			NS.life(callback, team);
			return;
		}
		/**/

		Brick.ajax('team', {
			'data': {
				'do': 'teammodulename',
				'teamid': teamid
			},
			'event': function(request){
				if (L.isValue(request) && L.isValue(request.data)){
					
					var mName = request.data;
					Brick.ff(mName, 'lib', function(){
						var mNS = Brick.mod[mName] || {};
						if (!L.isFunction(mNS['initManager'])){
							NS.life(callback, null);
						}else{
							mNS['initManager'](function(man){
								man.teamLoad(teamid, function(team){
									NS.life(callback, team);
								});
							});
						}
					});
				}else{
					NS.life(callback, null);
				}
			}
		});		
	};
};