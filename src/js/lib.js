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
                this.appStructure(function(){
                    this.initCallbackFire();
                }, this);
            }, this);
        },
        getTeamCache: function(teamid, name){
            teamid = teamid | 0;
            var cache = this._teamCache;
            if (!cache[teamid]){
                cache[teamid] = {};
            }
            return cache[teamid][name];
        },
        setTeamCache: function(teamid, name, obj){
            teamid = teamid | 0;
            var cache = this._teamCache;
            if (!cache[teamid]){
                cache[teamid] = {};
            }
            cache[teamid][name] = obj;
        },
        cleanTeamCache: function(teamid, name){
            teamid = teamid | 0;
            var cache = this._teamCache;
            if (teamid && name && cache[teamid] && cache[teamid][name]){
                delete cache[teamid][name];
            } else if (teamid && cache[teamid]){
                delete cache[teamid];
            } else if (!teamid && !name){
                this._teamCache = {};
            }
        },
    }, [], {
        APPS: {
            uprofile: {}
        },
        ATTRS: {
            isLoadAppStructure: {value: false},
            Team: {value: NS.Team},
            TeamList: {value: NS.TeamList},
            TeamListFilter: {value: NS.TeamListFilter},
            Member: {value: NS.Member},
            MemberList: {value: NS.MemberList},
            MemberListFilter: {value: NS.MemberListFilter},
            Config: {value: NS.Config}
        },
        REQS: {
            teamSave: {
                args: ['data']
            },
            team: {
                args: ['teamid'],
                type: 'model:Team',
                onResponse: function(team, data){
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
            memberList: {
                args: ['filter'],
                type: 'response:MemberListFilter',
                onResponse: function(filter){
                    var userIds = filter.get('items').toArray('userid', {distinct: true});

                    return function(callback, context){
                        this.getApp('uprofile').userListByIds(userIds, function(err, result){
                            callback.call(context || null);
                        }, context);
                    };
                }
            },
            memberSave: {
                args: ['teamid', 'data']
            }
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
        }
    });
};