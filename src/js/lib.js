var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['base.js']}
    ]
};
Component.entryPoint = function(NS){
    var COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isView: 10,
        isWrite: 20,
        isTeamAppend: 30,
        isAdmin: 50
    });

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            this._teamCache = {};
            NS.roles.load(function(){
                this.pluginList(function(){
                    this.initCallbackFire();
                }, this);
            }, this);
        },
        _teamidToKey: function(teamid){
            return 't' + (teamid | 0);
        },
        getTeamCache: function(teamid, name){
            teamid = this._teamidToKey(teamid);
            var cache = this._teamCache;
            if (!cache[teamid]){
                cache[teamid] = {};
            }
            return cache[teamid][name];
        },
        setTeamCache: function(teamid, name, obj){
            teamid = this._teamidToKey(teamid);
            var cache = this._teamCache;
            if (!cache[teamid]){
                cache[teamid] = {};
            }
            cache[teamid][name] = obj;
        },
        cleanTeamCache: function(teamid, name){
            teamid = this._teamidToKey(teamid);
            var cache = this._teamCache;
            if (teamid && name && cache[teamid] && cache[teamid][name]){
                delete cache[teamid][name];
            } else if (teamid && cache[teamid]){
                delete cache[teamid];
            } else if (!teamid && !name){
                this._teamCache = {};
            }
        },
        _memberExtends: function(member){
            var ownerModule = member.get('module'),
                extendApp = Brick.mod[ownerModule].appInstance,
                mExtends = member.get('extends');

            for (var className in mExtends){
                mExtends[className] = extendApp.instanceClass(className, mExtends[className]);
                mExtends[className].member = member;
            }
        }
    }, [], {
        APPS: {
            uprofile: {}
        },
        ATTRS: {
            isLoadAppStructure: {value: true},
            Policy: {value: NS.Policy},
            PolicyList: {value: NS.PolicyList},
            Action: {value: NS.Action},
            ActionList: {value: NS.ActionList},
            Role: {value: NS.Role},
            RoleList: {value: NS.RoleList},
            Plugin: {value: NS.Plugin},
            PluginList: {value: NS.PluginList},
            TeamMemberRole: {value: NS.TeamMemberRole},
            Team: {value: NS.Team},
            TeamList: {value: NS.TeamList},
            TeamListFilter: {value: NS.TeamListFilter},
            Member: {value: NS.Member},
            MemberList: {value: NS.MemberList},
            MemberListFilter: {value: NS.MemberListFilter},
            Config: {value: NS.Config}
        },
        REQS: {
            pluginList: {
                type: 'modelList:PluginList',
                attribute: true
            },
            teamSave: {
                args: ['data']
            },
            team: {
                args: ['teamid'],
                type: 'model:Team',
                cache: function(teamid){
                    return this.getTeamCache(teamid, 'Team');
                },
                onResponse: function(team, data){
                    this.setTeamCache(team.get('id'), 'Team', team);

                    return function(callback, context){
                        var ownerModule = team.get('module');
                        NS.initApps([ownerModule], function(){
                            var extendApp = Brick.mod[ownerModule].appInstance,
                                teamExtends = team.get('extends');

                            data.extends = data.extends || {};
                            for (var className in data.extends){
                                teamExtends[className] = extendApp.instanceClass(className, data.extends[className]);
                            }

                            callback.call(context || null);
                        }, this);
                    };
                }
            },
            teamList: {
                args: ['filter'],
                type: 'response:TeamListFilter'
            },
            member: {
                args: ['teamid', 'memberid'],
                type: "model:Member",
                onResponse: function(member){
                    return function(callback, context){
                        var ownerModule = member.get('module');

                        NS.initApps([ownerModule], function(){
                            this._memberExtends(member);

                            var userid = member.get('userid');
                            this.getApp('uprofile').user(userid, function(err, result){
                                callback.call(context || null);
                            }, context);
                        }, this);
                    };
                }
            },
            policies: {
                args: ['teamid'],
            },
            policyList: {
                args: ['teamid'],
                type: 'modelList:PolicyList',
                onResponse: function(policyList){
                    var i18n = this.language,
                        ownerI18n;

                    policyList.each(function(policy){
                        if (!policy.get('isSys')){
                            return;
                        }
                        if (!ownerI18n){
                            var team = this.getTeamCache(policy.get('teamid'), 'Team');
                            ownerI18n = Brick.mod[team.get('module')].appInstance.language;
                        }
                        var name = policy.get('name'),
                            key = 'policies.policy.' + name,
                            title = ownerI18n.get(key) || i18n.get(key);

                        policy.set('title', title ? title : name);
                    }, this);
                }
            },
            actionList: {
                args: ['teamid'],
                type: 'modelList:ActionList',
                onResponse: function(actionList){
                    var i18n = this.language;

                    actionList.each(function(action){
                        var ownerApp = Brick.mod[action.get('module')],
                            ownerI18n = ownerApp.appInstance.language,
                            name = action.get('group') + '.' + action.get('name'),
                            key = 'policies.action.item.' + name,
                            title = ownerI18n.get(key) || i18n.get(key);

                        action.set('title', title ? title : name);
                    }, this);
                }
            },
            roleList: {
                args: ['teamid'],
                type: 'modelList:RoleList',
            },
            memberList: {
                args: ['filter'],
                type: 'response:MemberListFilter',
                onResponse: function(filter){
                    return function(callback, context){
                        var memberList = filter.get('items');

                        var userIds = memberList.toArray('id', {distinct: true});
                        this.getApp('uprofile').userListByIds(userIds, function(err, result){
                            callback.call(context || null);
                        }, context);

                        /*
                        var ownerModules = memberList.toArray('module', {distinct: true});

                        NS.initApps(ownerModules, function(){
                            memberList.each(this._memberExtends, this);

                            var userIds = memberList.toArray('userid', {distinct: true});
                            this.getApp('uprofile').userListByIds(userIds, function(err, result){
                                callback.call(context || null);
                            }, context);

                        }, this);
                        /**/
                    };
                }
            },
            memberSave: {
                args: ['data']
            }
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
            team: {
                create: function(){
                    return this.getURL('ws') + 'teamEditor/TeamCreateWidget/';
                },
                ws: function(teamid, ownerModule){
                    var ret = "#app={C#MODNAMEURI}/wspace/item/" + (teamid | 0) + '/';
                    if (ownerModule){
                        ret += ownerModule + '/';
                    }
                    return ret;
                },
                edit: function(teamid, ownerModule){
                    var ret = this.getURL('ws') + 'teamEditor/TeamWrapEditorWidget/' + (teamid | 0) + '/';
                    if (ownerModule){
                        ret += ownerModule + '/';
                    }
                    return ret;
                },
                config: {
                    view: function(teamid){
                        return this.getURL('team.ws', teamid) + 'team/';
                    },
                    admins: function(teamid){
                        return this.getURL('team.ws', teamid) + 'team/teamAdminList/TeamAdminListWidget/';
                    },
                    policies: function(teamid){
                        return this.getURL('team.ws', teamid) + 'team/teamPolicies/TeamPoliciesWidget/';
                    },
                    roles: function(teamid){
                        return this.getURL('team.ws', teamid) + 'team/teamRoles/TeamRolesWidget/';
                    },
                },
            },
        }
    });
};